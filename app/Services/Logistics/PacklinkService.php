<?php

namespace App\Services\Logistics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PacklinkService
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string $baseUrl = 'https://api.packlink.com/v1'
    ) {
    }

    /**
     * Fabrique un service Packlink configuré à partir des variables d’environnement.
     */
    public static function make(): self
    {
        $env = config('services.packlink.env', 'production');
        $base = match ($env) {
            'sandbox', 'test' => 'https://apisandbox.packlink.com/v1',
            default => 'https://api.packlink.com/v1',
        };

        return new self(
            config('services.packlink.api_key'),
            rtrim(config('services.packlink.base_url', $base), '/')
        );
    }

    /**
     * Retourne vrai si une clé API est configurée.
     */
    public function enabled(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Récupère les offres Packlink pour un trajet donné.
     *
     * @param  array  $from  Adresse d’expédition (country, zip, city).
     * @param  array  $to    Adresse de livraison (country, zip, city).
     * @param  float  $weightKg Poids du colis en kilogrammes.
     * @param  float  $value Valeur assurée du contenu.
     * @return Collection Liste des offres.
     */
    public function quotes(array $from, array $to, float $weightKg, float $value = 0): Collection
    {
        // Vérification de la clé API
        if (!$this->enabled()) {
            session()->put('packlink_debug', 'Packlink API key missing');
            return collect();
        }

        // Vérification des champs obligatoires
        foreach (['country', 'zip'] as $field) {
            if (empty($from[$field]) || empty($to[$field])) {
                session()->put('packlink_debug', "Packlink: missing required address fields ($field)");
                return collect();
            }
        }

        // Préparation des dimensions du premier colis
        $package = [
            'weight' => max(0.2, round($weightKg, 3)),
            'height' => (float)($from['height'] ?? 10),
            'width'  => (float)($from['width']  ?? 10),
            'length' => (float)($from['length'] ?? 10),
        ];

        // Corps JSON pour les requêtes POST
        $payload = [
            'from' => [
                'country' => strtoupper($from['country']),
                'zip'     => $from['zip'],
                'city'    => $from['city'] ?? '',
            ],
            'to' => [
                'country' => strtoupper($to['country']),
                'zip'     => $to['zip'],
                'city'    => $to['city'] ?? '',
            ],
            'packages'      => [$package],
            'content_value' => round($value, 2),
        ];

        // Paramètres pour les requêtes GET (avec packages[0][…])
        $query = [
            'from[country]'      => strtoupper($from['country']),
            'from[zip]'          => $from['zip'],
            'from[city]'         => $from['city'] ?? '',
            'to[country]'        => strtoupper($to['country']),
            'to[zip]'            => $to['zip'],
            'to[city]'           => $to['city'] ?? '',
            'packages[0][weight]' => $package['weight'],
            'packages[0][height]' => $package['height'],
            'packages[0][width]'  => $package['width'],
            'packages[0][length]' => $package['length'],
            'content_value'      => round($value, 2),
        ];

        try {
            $http = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(8);

            // On privilégie le endpoint officiel POST /shipments/quote,
            // qui renvoie une liste de services disponibles.
            $url = rtrim($this->baseUrl, '/') . '/shipments/quote';
            $response = $http->post($url, $payload);

            // Si la route POST renvoie une 404, on tente la version GET /shipments/quote
            if ($response->status() === 404) {
                $response = $http->get($url, $query);
            }

            if (!$response->ok()) {
                session()->put('packlink_debug', 'Packlink error ' . $response->status());
                session()->put('packlink_response', [
                    'status'  => $response->status(),
                    'body'    => substr($response->body() ?? '', 0, 2000),
                    'payload' => $payload,
                    'query'   => $query,
                ]);
                return collect();
            }

            session()->forget('packlink_debug');

            $payload = $response->json();
            // Certains retours sont encapsulés dans data.available_services
            $services = data_get($payload, 'data.available_services', $payload['data'] ?? $payload);

            // Normalisation de la liste
            if (!is_array($services)) {
                $services = [];
            }
            if (!array_is_list($services)) {
                $services = array_values($services);
            }

            // Transformation en collection avec les champs normalisés
            return collect($services)->map(function ($quote) {
                $serviceId = data_get($quote, 'service_id') ?? data_get($quote, 'id');
                $price     = data_get($quote, 'price.amount') ?? data_get($quote, 'price.value') ?? data_get($quote, 'price');
                $delivery  = data_get($quote, 'delivery_time') ?? data_get($quote, 'duration') ?? data_get($quote, 'eta');

                return [
                    'service_id' => $serviceId,
                    'name'       => data_get($quote, 'name') ?? 'Transporteur',
                    'price'      => is_numeric($price) ? (float)$price : null,
                    'delay'      => $delivery,
                    'type'       => Str::contains(Str::lower(data_get($quote, 'name', '')), 'relay') ? 'relay' : 'shipping',
                    'raw'        => $quote,
                ];
            })->filter(fn ($quote) => $quote['service_id'] && $quote['price'] !== null);
        } catch (\Throwable $e) {
            session()->put('packlink_debug', 'Packlink exception: ' . $e->getMessage());
            session()->put('packlink_response', [
                'exception' => $e->getMessage(),
                'payload'   => $payload,
            ]);
            return collect();
        }
    }
}
