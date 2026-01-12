<?php

namespace App\Services\Logistics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BoxtalService
{
    public function __construct(
        private readonly ?string $key = null,
        private readonly ?string $secret = null,
        private readonly ?string $login = null,
        private readonly ?string $password = null,
        private readonly string $baseUrl = 'https://test.envoimoinscher.com/api/v1',
    ) {
    }

    public static function make(): self
    {
        $cfg = config('services.boxtal');
        return new self(
            $cfg['key'] ?? null,
            $cfg['secret'] ?? null,
            $cfg['login'] ?? null,
            $cfg['password'] ?? null,
            rtrim($cfg['base_url'] ?? 'https://test.envoimoinscher.com/api/v1', '/')
        );
    }

    public function enabled(): bool
    {
        return (filled($this->key) && filled($this->secret)) || (filled($this->login) && filled($this->password));
    }

    /**
     * Récupère des offres Boxtal v1 (XML) via /cotation.
     *
     * @param array $from ['country','zip','city','type']
     * @param array $to ['country','zip','city','type']
     * @param array $packages liste de colis [['weight','length','width','height','value']]
     * @param string $contentCode code_contenu obligatoire (5 chiffres)
     */
    public function quotes(array $from, array $to, array $packages, string $contentCode = '10150'): Collection
    {
        if (! $this->enabled()) {
            session()->put('boxtal_debug', 'Boxtal: credentials manquants');
            return collect();
        }

        if (! function_exists('simplexml_load_string')) {
            session()->put('boxtal_debug', 'Boxtal: extension SimpleXML manquante côté serveur');
            session()->put('boxtal_response', [
                'query' => [],
                'base' => $this->baseUrl.'/cotation',
                'attempts' => [],
            ]);
            return collect();
        }

        // Construire la query selon le format v1 (colis_1.*, expediteur.*, destinataire.*)
        $query = [
            'code_contenu' => $contentCode,
            'expediteur.pays' => $from['country'] ?? '',
            'expediteur.code_postal' => $from['zip'] ?? '',
            'expediteur.ville' => $from['city'] ?? '',
            'expediteur.type' => $from['type'] ?? 'entreprise',
            'destinataire.pays' => $to['country'] ?? '',
            'destinataire.code_postal' => $to['zip'] ?? '',
            'destinataire.ville' => $to['city'] ?? '',
            'destinataire.type' => $to['type'] ?? 'particulier',
        ];

        foreach ($packages as $idx => $pkg) {
            $n = $idx + 1;
            $query["colis_{$n}.poids"] = $pkg['weight'];
            $query["colis_{$n}.longueur"] = $pkg['length'];
            $query["colis_{$n}.largeur"] = $pkg['width'];
            $query["colis_{$n}.hauteur"] = $pkg['height'];
            if (isset($pkg['value'])) {
                $query["colis_{$n}.valeur"] = $pkg['value'];
            }
        }

        try {
            $attempts = [];
            $authUser = $this->key ?: $this->login;
            $authPass = $this->secret ?: $this->password;
            if (!filled($authUser) || !filled($authPass)) {
                session()->put('boxtal_debug', 'Boxtal: credentials manquants');
                return collect();
            }

            $attempts = [];
            // Try with Accept XML
            $response = Http::withBasicAuth($authUser, $authPass)
                ->withHeaders(['Accept' => 'application/xml'])
                ->timeout(12)
                ->get($this->baseUrl . '/cotation', $query);
            $attempts[] = ['accept' => 'application/xml', 'status' => $response->status(), 'body' => substr($response->body(), 0, 500)];

            // If 406/415 or body empty, retry without forcing Accept
            if (in_array($response->status(), [406, 415]) || trim($response->body()) === '') {
                $response = Http::withBasicAuth($authUser, $authPass)
                    ->timeout(12)
                    ->get($this->baseUrl . '/cotation', $query + ['format' => 'xml']);
                $attempts[] = ['accept' => null, 'status' => $response->status(), 'body' => substr($response->body(), 0, 500)];
            }

            session()->put('boxtal_response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'query' => $query,
                'base' => $this->baseUrl.'/cotation',
                'attempts' => $attempts,
            ]);

            if (! $response->ok()) {
                session()->put('boxtal_debug', 'Boxtal erreur '.$response->status());
                return collect();
            }

            $xml = @simplexml_load_string($response->body());
            if (! $xml) {
                session()->put('boxtal_debug', 'Boxtal: réponse XML invalide');
                return collect();
            }

            $offers = [];
            // Récupère toutes les offres où qu'elles se trouvent (cotation/shipment/offer)
            $offerNodes = $xml->xpath('//offer') ?: [];
            foreach ($offerNodes as $offer) {
                $operatorCode = (string) ($offer->operator->code ?? '');
                $operatorLabel = (string) ($offer->operator->label ?? '');
                $serviceCode = (string) ($offer->service->code ?? '');
                $serviceLabel = (string) ($offer->service->label ?? '');
                $deliveryLabel = (string) ($offer->delivery->label ?? '');
                $priceTtc = (float) ($offer->price->{'tax-inclusive'} ?? 0);
                $delayRaw = (string) ($offer->collection->date ?? ($offer->delivery->date ?? ''));
                $delayDate = $this->extractDeliveryDate($offer, $deliveryLabel, $delayRaw);
                $delayDate = $this->addDaysToDate($delayDate ?: $delayRaw, 2);
                $priceTtc = $this->roundPriceTo90($priceTtc);

                $operatorCodeUpper = strtoupper($operatorCode);
                $serviceCodeUpper = strtoupper($serviceCode);
                $niceName = trim($this->formatOperatorLabel($operatorLabel, $operatorCode) . ' ' . $this->formatServiceLabel($serviceLabel));
                $type = 'shipping';

                if ($operatorCodeUpper === 'CHRP' && str_contains($serviceCodeUpper, 'SHOP')) {
                    $niceName = 'Chronopost Shop2Shop - Point relais';
                    $type = 'relay';
                } elseif ($operatorCodeUpper === 'MONR') {
                    $niceName = 'Mondial Relay - Point relais';
                    $type = 'relay';
                } elseif ($operatorCodeUpper === 'COLI') {
                    $service = $this->formatServiceLabel($serviceLabel);
                    if (! str_contains(mb_strtoupper($service), 'DOMICILE')) {
                        $service = 'Domicile - '.$service;
                    }
                    // Normalise "Domicile Sans Signature" -> "Domicile - Sans Signature"
                    $service = preg_replace('/Domicile\\s+(Sans|Avec)/iu', 'Domicile - $1', $service) ?? $service;
                    $niceName = trim('La Poste Colissimo '.$service);
                }

                $offers[] = [
                    'method_id' => "boxtal:{$operatorCode}:{$serviceCode}",
                    'name' => $niceName ?: trim($operatorLabel.' '.$serviceLabel.' '.$deliveryLabel),
                    'price' => $priceTtc,
                    'delay' => $delayDate ?: $delayRaw,
                    'type' => $type,
                    'origin' => 'boxtal',
                    'operator' => $operatorCode,
                    'service' => $serviceCode,
                    // Ne pas conserver l'objet XML brut dans la session (serialization forbidden)
                ];
            }

            // Ne garder que les 3 services souhaités (filtre large pour éviter les variantes de codes)
            $offersBeforeFilter = $offers;
            $offers = array_values(array_filter($offers, function ($offer) {
                $op = strtoupper($offer['operator'] ?? '');
                $svc = strtoupper($offer['service'] ?? '');
                $name = strtoupper($offer['name'] ?? '');

                // Mondial Relay point relais (on ne garde que CpourToi)
                $isMonr = $op === 'MONR' && str_contains($svc, 'CPOURTOI');

                // Chronopost Shop2Shop
                $isChrpShop = $op === 'CHRP' && (str_contains($svc, 'SHOP') || str_contains($name, 'SHOP2SHOP'));

                // Colissimo domicile
                $isColissimo = ($op === 'COLI' || str_contains($name, 'COLISSIMO'))
                    && (str_contains($svc, 'DOM') || str_contains($name, 'DOMICILE'));

                return $isMonr || $isChrpShop || $isColissimo;
            }));

            session()->put('boxtal_response', array_merge(session('boxtal_response', []), [
                'offers_before_filter' => count($offersBeforeFilter),
                'offers_after_filter' => count($offers),
                'operators_after_filter' => collect($offers)->map(fn ($o) => ($o['operator'] ?? '').'/'.($o['service'] ?? ''))->values()->all(),
            ]));

            if (empty($offers)) {
                session()->put('boxtal_debug', 'Boxtal: aucune offre retournée');
                session()->put('boxtal_response', array_merge(session('boxtal_response', []), [
                    'offers_found' => 0,
                    'offer_nodes_count' => is_iterable($offerNodes) ? count($offerNodes) : 0,
                ]));
            } else {
                session()->forget('boxtal_debug');
            }

            return collect($offers);
        } catch (\Throwable $e) {
            Log::error('Boxtal quotes error', ['message' => $e->getMessage()]);
            session()->put('boxtal_debug', 'Boxtal exception: '.$e->getMessage());
            session()->put('boxtal_response', [
                'status' => null,
                'body' => null,
                'query' => $query ?? [],
                'base' => $this->baseUrl.'/cotation',
                'attempts' => $attempts ?? [],
                'exception' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    private function roundPriceTo90(float $price): float
    {
        $euros = floor($price);
        $rounded = $euros + 0.9;
        if ($rounded < $price) {
            $rounded = $euros + 1 + 0.9;
        }
        return round($rounded, 2);
    }

    private function formatOperatorLabel(string $label, string $code): string
    {
        $label = trim($label);
        return trim($label);
    }

    private function formatServiceLabel(string $label): string
    {
        $label = trim($label);
        return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    }

    private function extractDeliveryDate($offer, string $deliveryLabel, string $fallback = ''): string
    {
        if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $deliveryLabel, $m)) {
            return $m[1];
        }
        $date = (string) ($offer->delivery->date ?? '');
        if (!empty($date)) {
            return $date;
        }
        return $fallback;
    }

    private function addDaysToDate(string $date, int $days): string
    {
        try {
            if (str_contains($date, '/')) {
                $dt = \DateTime::createFromFormat('d/m/Y', $date);
            } else {
                $dt = new \DateTime($date);
            }
            if ($dt === false) {
                return $date;
            }
            $dt->modify('+' . $days . ' days');
            return $dt->format(str_contains($date, '/') ? 'd/m/Y' : 'Y-m-d');
        } catch (\Throwable $e) {
            return $date;
        }
    }
}
