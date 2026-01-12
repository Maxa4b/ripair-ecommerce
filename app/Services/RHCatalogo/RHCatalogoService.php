<?php

namespace App\Services\RHCatalogo;

use App\Models\Catalog\Product;
use App\Models\Legacy\Repair;
use App\Services\RHCatalogo\Exceptions\LlmUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RHCatalogoService
{
    private function shouldPauseOnLlmUnavailable(): bool
    {
        return (bool) config('rhcatalogo.pause_on_llm_unavailable', false);
    }

    private function deriveLlmProbeUrl(string $endpoint): string
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') {
            return '';
        }

        $normalized = rtrim($endpoint, '/');
        $normalized = preg_replace('#/v1/(chat/)?completions$#i', '/v1/models', $normalized) ?? $normalized;
        if (Str::endsWith(Str::lower($normalized), '/v1/models')) {
            return $normalized;
        }

        $parts = parse_url($endpoint);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return $normalized;
        }

        $base = $parts['scheme'].'://'.$parts['host'];
        if (isset($parts['port'])) {
            $base .= ':'.$parts['port'];
        }

        return $base.'/v1/models';
    }

    private function isLlmUnavailableThrowable(\Throwable $e): bool
    {
        if ($e instanceof LlmUnavailableException) {
            return true;
        }
        if ($e instanceof ConnectionException) {
            return true;
        }

        $msg = Str::lower($e->getMessage());
        return Str::contains($msg, [
            'connection refused',
            'could not resolve host',
            'operation timed out',
            'curl error 7',
            'curl error 28',
        ]);
    }

    public function waitForLlmAvailability(?string $endpoint = null): void
    {
        $endpoint = $endpoint ?? (string) config('rhcatalogo.openai.endpoint');
        $probeUrl = $this->deriveLlmProbeUrl($endpoint);
        if ($probeUrl === '') {
            return;
        }

        $timeout = (int) config('rhcatalogo.pause_probe_timeout', 3);
        $sleepSeconds = (int) config('rhcatalogo.pause_check_seconds', 15);
        $apiKey = (string) config('rhcatalogo.openai.key');

        while (true) {
            try {
                $resp = Http::timeout(max(1, $timeout))
                    ->when($apiKey !== '', fn ($h) => $h->withToken($apiKey))
                    ->get($probeUrl);

                if ($resp->ok()) {
                    return;
                }
            } catch (\Throwable $e) {
                // still unavailable
            }

            sleep(max(1, $sleepSeconds));
        }
    }

    private function encodeJson(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function limitText(?string $text, int $maxChars): ?string
    {
        $text = $text === null ? null : trim($text);
        if ($text === null || $text === '') {
            return null;
        }
        return Str::length($text) > $maxChars ? Str::limit($text, $maxChars, '...') : $text;
    }
    /**
     * @return array{0:\Illuminate\Http\Client\Response,1:array{retryable:bool,error_type?:string,error_code?:string}}
     */
    private function postOpenAiWithRetry(string $endpoint, string $apiKey, int $timeout, array $payload, int $maxAttempts = 4): array
    {
        $attempt = 0;
        $meta = ['retryable' => false];

        while (true) {
            $attempt++;
            $resp = Http::timeout($timeout)->withToken($apiKey)->post($endpoint, $payload);

            if ($resp->ok()) {
                return [$resp, $meta];
            }

            $status = $resp->status();

            $errorType = null;
            $errorCode = null;
            try {
                $decoded = $resp->json();
                if (is_array($decoded) && isset($decoded['error']) && is_array($decoded['error'])) {
                    $errorType = isset($decoded['error']['type']) ? (string) $decoded['error']['type'] : null;
                    $errorCode = isset($decoded['error']['code']) ? (string) $decoded['error']['code'] : null;
                }
            } catch (\Throwable $e) {
                $errorType = null;
                $errorCode = null;
            }

            $meta = [
                'retryable' => false,
                'error_type' => $errorType ?: null,
                'error_code' => $errorCode ?: null,
            ];

            $isTransient = in_array($status, [429, 500, 502, 503, 504], true);
            $isQuota = in_array($errorType, ['insufficient_quota', 'billing_hard_limit_reached'], true);

            if (!$isTransient || $isQuota || $attempt >= $maxAttempts) {
                return [$resp, $meta];
            }

            $meta['retryable'] = true;

            $retryAfterMs = null;
            $retryAfter = trim((string) ($resp->header('Retry-After') ?? ''));
            if ($retryAfter !== '' && ctype_digit($retryAfter)) {
                $retryAfterMs = ((int) $retryAfter) * 1000;
            } else {
                $reset = trim((string) ($resp->header('x-ratelimit-reset-requests') ?? ''));
                if ($reset !== '' && preg_match('/(\\d+(?:\\.\\d+)?)s/i', $reset, $m) === 1) {
                    $retryAfterMs = (int) (floatval($m[1]) * 1000);
                }
            }

            // Exponential backoff with jitter (ms)
            $base = 400 * (2 ** ($attempt - 1));
            $sleepMs = $retryAfterMs !== null
                ? min(10000, max(0, $retryAfterMs))
                : min(5000, $base + random_int(0, 250));
            usleep($sleepMs * 1000);
        }
    }

    private function normalizeForMatch(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9]+/i', ' ', $text) ?? $text;
        $text = preg_replace('/\\s+/', ' ', $text) ?? $text;
        return trim($text);
    }

    /**
     * Retire les mentions d'appareil/modele (ex: "pour iPhone 13 Mini") du libelle final.
     *
     * @param array{models:Collection<int,string>} $ctx
     */
    private function stripDeviceMentionsFromPieceLabel(array $ctx, string $label): string
    {
        $label = $this->cleanText($label);
        if ($label === '') {
            return '';
        }

        $deviceNeedles = [
            'iphone', 'ipad', 'ipod', 'apple',
            'samsung', 'galaxy',
            'xiaomi', 'redmi', 'poco',
            'huawei', 'honor',
            'oppo', 'vivo', 'oneplus',
            'google', 'pixel',
            'sony', 'playstation', 'ps5', 'ps4',
            'nintendo', 'switch',
            'xbox',
            'macbook', 'imac', 'surface',
        ];

        foreach (['pour', 'for'] as $kw) {
            if (preg_match('/\\s+'.$kw.'\\s+(.+)$/iu', $label, $m) === 1) {
                $tail = (string) ($m[1] ?? '');
                $tailNorm = $this->normalizeForMatch($tail);

                $looksLikeDevice = preg_match('/\\d/', $tail) === 1;
                if (!$looksLikeDevice) {
                    foreach ($deviceNeedles as $needle) {
                        if (Str::contains($tailNorm, $needle)) {
                            $looksLikeDevice = true;
                            break;
                        }
                    }
                }

                if ($looksLikeDevice) {
                    $label = preg_replace('/\\s+'.$kw.'\\s+.+$/iu', '', $label) ?? $label;
                }
            }
        }

        $models = $ctx['models'] ?? null;
        if ($models instanceof Collection) {
            foreach ($models->take(20) as $model) {
                $m = trim((string) $model);
                if ($m === '' || preg_match('/\\d/', $m) !== 1) {
                    continue;
                }
                $label = str_ireplace($m, '', $label);
            }
        }

        // Enleve aussi les devices qui apparaissent sans "pour" (ex: "... iPhone 13 Mini").
        if (preg_match('/\\d/', $label) === 1) {
            $label = preg_replace('/\\b(?:iPhone|iPad|iPod|Samsung|Galaxy|Xiaomi|Redmi|Poco|Huawei|Honor|Oppo|Vivo|OnePlus|Google|Pixel|Sony|PlayStation|PS5|PS4|Nintendo|Switch|Xbox|MacBook|iMac|Surface)\\b.*$/iu', '', $label) ?? $label;
        }

        $label = preg_replace('/\\s{2,}/', ' ', $label) ?? $label;
        // Enlève les compléments "avec ... préinstallé" qui polluent les titres.
        $label = preg_replace('/\\bavec\\b[^.]*\\bpre\\s*installe\\w*\\b/iu', '', $label) ?? $label;
        $label = preg_replace('/\\bavec\\b[^.]*\\bpreinstalled\\b/iu', '', $label) ?? $label;
        $label = preg_replace('/\\bpre\\s*installe\\w*\\b/iu', '', $label) ?? $label;
        $label = preg_replace('/\\bpreinstalled\\b/iu', '', $label) ?? $label;
        $label = preg_replace('/\\bflexionnaire(s)?\\b/iu', 'flex$1', $label) ?? $label;
        $label = preg_replace('/\\bflex\\s+cable\\b/iu', 'cable flex', $label) ?? $label;
        $label = preg_replace('/\\bcable\\s+flexionnaire\\b/iu', 'cable flex', $label) ?? $label;
        $label = preg_replace('/\\s{2,}/', ' ', $label) ?? $label;
        $label = trim($label, " \t\n\r\0\x0B-–—,;/");
        $label = trim($label, " \t\n\r\0\x0B-–—,;/");
        return $label;
    }

    /**
     * Règles génériques pour valider le sens d'un libellé FR (évite les contresens du LLM local).
     *
     * @return array<int,array{needles:array<int,string>,expects:array<int,string>}>
     */
    private function pieceLabelExpectationsFromSource(string $source): array
    {
        $rules = [
            ['needles' => ['assembly', 'assembled'], 'expects' => ['ensemble', 'assemble', 'module', 'ecran']],
            ['needles' => ['screw', 'screws'], 'expects' => ['vis']],
            ['needles' => ['motor', 'vibration motor', 'vibrator'], 'expects' => ['moteur', 'vibreur', 'vibration']],
            ['needles' => ['charging port', 'charge port', 'charger port'], 'expects' => ['connecteur', 'charge']],
            ['needles' => ['battery fpc', 'batt fpc'], 'expects' => ['connecteur', 'batterie', 'fpc']],
            ['needles' => ['battery'], 'expects' => ['batterie']],
            ['needles' => ['screen', 'lcd', 'oled'], 'expects' => ['ecran', 'lcd', 'oled']],
            ['needles' => ['camera'], 'expects' => ['camera']],
            ['needles' => ['lens'], 'expects' => ['lentille']],
            ['needles' => ['bracket'], 'expects' => ['support']],
            ['needles' => ['bezel'], 'expects' => ['contour', 'bezel']],
            ['needles' => ['speaker'], 'expects' => ['haut parleur', 'ecouteur', 'speaker']],
            ['needles' => ['microphone', 'mic'], 'expects' => ['micro']],
            ['needles' => ['connector', 'port'], 'expects' => ['connecteur']],
            ['needles' => ['flex', 'fpc'], 'expects' => ['nappe', 'flex', 'fpc', 'connecteur']],
            ['needles' => ['housing', 'shell', 'back cover', 'rear cover'], 'expects' => ['coque', 'chassis', 'boitier']],
        ];

        $matches = [];
        foreach ($rules as $r) {
            foreach ($r['needles'] as $needle) {
                if (Str::contains($source, $needle)) {
                    $matches[] = $r;
                    break;
                }
            }
        }

        return $matches;
    }

    private function isPieceLabelPlausible(array $ctx, string $pieceLabel): bool
    {
        $label = $this->normalizeForMatch($pieceLabel);
        if ($label === '') {
            return false;
        }

        $source = $this->normalizeForMatch(implode(' ', [
            (string) ($ctx['supplier_title'] ?? ''),
            (string) ($ctx['supplier_product_description'] ?? ''),
            (string) ($ctx['supplier_description'] ?? ''),
            (string) ($ctx['supplier_text'] ?? ''),
            (string) ($ctx['supplier_url'] ?? ''),
        ]));

        // Anti-contresens : ne pas confondre écran/module écran avec nappe/flex.
        // Si la page parle d'un écran (OLED/LCD/screen/assembly/display/frame) et ne parle pas explicitement de flex/fpc/cable,
        // alors un libellé "nappe/flex" est très probablement faux.
        $sourceLooksLikeScreen = Str::contains($source, ['screen', 'display', 'lcd', 'oled', 'assembly', 'frame', 'with frame', 'with bezel']);
        $sourceLooksLikeFlex = Str::contains($source, ['flex', 'fpc', 'ribbon', 'cable', 'connector']);
        $labelLooksLikeFlex = Str::contains($label, ['nappe', 'flex', 'fpc', 'cable']);
        $labelLooksLikeScreen = Str::contains($label, ['ecran', 'lcd', 'oled', 'affichage', 'module']);

        if ($sourceLooksLikeScreen && !$sourceLooksLikeFlex && $labelLooksLikeFlex && !$labelLooksLikeScreen) {
            return false;
        }

        // Inverse : si la source parle explicitement de flex/fpc/cable et ne parle pas d'écran,
        // alors un libellé "écran/module" est probablement faux.
        if ($sourceLooksLikeFlex && !$sourceLooksLikeScreen && $labelLooksLikeScreen && !$labelLooksLikeFlex) {
            return false;
        }

        $expectations = $this->pieceLabelExpectationsFromSource($source);
        if (empty($expectations)) {
            return true;
        }

        foreach ($expectations as $e) {
            foreach ($e['expects'] as $expected) {
                if (Str::contains($label, $expected)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function detectQuantityHint(array $ctx): ?int
    {
        $candidates = [
            (string) ($ctx['supplier_product_description'] ?? ''),
            (string) ($ctx['supplier_title'] ?? ''),
            (string) ($ctx['supplier_description'] ?? ''),
            (string) ($ctx['supplier_url'] ?? ''),
        ];

        foreach ($candidates as $text) {
            $t = $this->normalizeForMatch($text);
            if ($t === '') {
                continue;
            }

            if (preg_match('/\\b(\\d{1,4})\\s*(?:piece|pieces|pcs)\\b/i', $t, $m) === 1) {
                return (int) $m[1];
            }
            if (preg_match('/\\b(\\d{1,4})\\s*pack\\b/i', $t, $m) === 1) {
                return (int) $m[1];
            }
            if (preg_match('/\\bcontains\\s+(\\d{1,4})\\b/i', $t, $m) === 1) {
                return (int) $m[1];
            }
            if (preg_match('/\\b(\\d{1,4})\\s*[- ]?pieces\\b/i', $t, $m) === 1) {
                return (int) $m[1];
            }
        }

        return null;
    }

    /**
     * Fallback déterministe quand le LLM local sort un contresens.
     */
    private function fallbackFrenchPieceLabel(array $ctx): string
    {
        $supplierTitle = trim((string) ($ctx['supplier_title'] ?? ''));
        $supplierProductDescription = trim((string) ($ctx['supplier_product_description'] ?? ''));
        $raw = $supplierTitle !== '' ? $supplierTitle : $supplierProductDescription;
        $raw = $this->cleanText($raw);

        if ($raw === '') {
            return $ctx['current_problem'] ?? 'Piece detachee';
        }

        // Enlève la cible "for <device...>" pour ne garder que la pièce.
        $raw = preg_replace('/\\s+for\\s+.+$/i', '', $raw) ?? $raw;
        $raw = preg_replace('/\\bcompatible\\s+for\\b\\s*/i', '', $raw) ?? $raw;

        // Enlève parenthèses de quantité (ex: "(100 Pack)") mais garde les qualificatifs (ex: "(Y-Tip)").
        $raw = preg_replace('/\\([^)]*\\b(\\d+\\s*(?:pack|piece|pieces|pcs))\\b[^)]*\\)/i', '', $raw) ?? $raw;
        $raw = preg_replace('/\\s{2,}/', ' ', $raw) ?? $raw;
        $raw = trim($raw, " \t\n\r\0\x0B-–—/");

        // Traduction générique via mini-glossaire (non exhaustif).
        $map = [
            'power button flex cable' => 'Cable flex du bouton power',
            'power button cable' => 'Cable du bouton power',
            'power button' => 'Bouton power',
            'flex cable' => 'Cable flex',
            'flex' => 'Flex',
            'back cover adhesive tape' => 'Bande adhésive pour cache arrière',
            'rear cover adhesive tape' => 'Bande adhésive pour cache arrière',
            'adhesive tape' => 'Bande adhésive',
            'adhesive' => 'Adhésif',
            'tape' => 'Bande',
            'vibration motor' => 'Moteur vibreur',
            'motor vibration' => 'Moteur vibreur',
            'vibrator motor' => 'Moteur vibreur',
            'vibrator' => 'Vibreur',
            'motor' => 'Moteur',
            'assembly' => 'Ensemble',
            'assembled' => 'Assemble',
            'screw set' => 'Kit de vis',
            'screws' => 'Vis',
            'screw' => 'Vis',
            'rear shell' => 'Coque arriere',
            'rear housing' => 'Coque arriere',
            'back cover' => 'Cache arrière',
            'rear cover' => 'Cache arrière',
            'front shell' => 'Coque avant',
            'front housing' => 'Coque avant',
            'shell' => 'Coque',
            'housing' => 'Coque',
            'rear' => 'Arriere',
            'front' => 'Avant',
            'y-tip' => 'Embout Y',
            'tri-wing' => 'Tri-wing',
            'battery fpc' => 'Connecteur batterie (FPC)',
            'batt fpc' => 'Connecteur batterie (FPC)',
            'battery' => 'Batterie',
            'charging port' => 'Connecteur de charge',
            'charge port' => 'Connecteur de charge',
            'charger port' => 'Connecteur de charge',
            'screen' => 'Ecran',
            'camera lens' => 'Lentille camera',
            'lens' => 'Lentille',
            'bracket' => 'Support',
            'bezel' => 'Contour',
            'back camera' => 'Camera arriere',
            'rear camera' => 'Camera arriere',
            'front camera' => 'Camera avant',
            'loud speaker' => 'Haut-parleur',
            'ear speaker' => 'Ecouteur interne',
            'speaker' => 'Haut-parleur',
            'microphone' => 'Micro',
            'mic' => 'Micro',
            'with' => 'Avec',
        ];

        $label = ' '.$raw.' ';
        foreach ($map as $from => $to) {
            $label = preg_replace('/\\b'.preg_quote($from, '/').'\\b/i', $to, $label) ?? $label;
        }
        $label = $this->cleanText($label);

        $qty = $this->detectQuantityHint($ctx);
        return Str::limit($label, 110, '');
    }
    /**
     * Pipeline "fiable" :
     * - Source de vérité : `repairs` (supplier_ref + supplier_url)
     * - Scrape fournisseur, puis IA pour libellé + description
     * - Sync `repairs.problem` + `products.*` (via ensureVariant)
     *
     * @param array{dry_run?:bool,reset_products?:bool} $options
     * @return array{supplier_ref:string,piece_label:string,description:string,repairs_updated:int,products_updated:int}
     */
    public function processSupplierRef(string $supplierRef, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $resetProducts = (bool) ($options['reset_products'] ?? false);
        $debugEnabled = (bool) ($options['debug'] ?? false);
        $debugHtml = (bool) ($options['debug_html'] ?? false);
        $includeDone = (bool) ($options['include_done'] ?? false);
        $persistPayload = (bool) ($options['persist_payload'] ?? true);

        $capture = $debugEnabled || $persistPayload;
        $debug = $capture ? ['supplier_ref' => (string) $supplierRef] : [];

        $repairs = Repair::query()
            ->where('supplier_ref', $supplierRef)
            ->orderByDesc('id')
            ->get();

        if ($repairs->isEmpty()) {
            throw new \RuntimeException("No repair found for supplier_ref={$supplierRef}");
        }

        $repairsToUpdate = $repairs
            ->filter(fn (Repair $r) => $includeDone ? true : !$r->update_done)
            ->values();

        if (!$includeDone && $repairsToUpdate->isEmpty()) {
            if (!$dryRun) {
                $now = now();
                Product::query()
                    ->where('supplier_reference', $supplierRef)
                    ->update([
                        'rhc_status' => 'ok',
                        'rhc_last_error' => null,
                        'rhc_last_run_at' => $now,
                        'rhc_last_payload' => $persistPayload ? $this->encodeJson([
                            'stage' => 'skipped',
                            'reason' => 'update_done_all_repairs',
                            'supplier_ref' => $supplierRef,
                            'at' => $now->toISOString(),
                        ]) : null,
                    ]);
            }

            return [
                'supplier_ref' => (string) $supplierRef,
                'piece_label' => (string) ($repairs->first()?->problem ?? ''),
                'description' => '',
                'repairs_updated' => 0,
                'products_updated' => 0,
            ];
        }

        [$representative, $supplierData] = $this->resolveSupplierPage($repairs, $capture, $debugEnabled ? $debugHtml : false, $debug);

        if ($capture) {
            $debug['representative_repair_id'] = (int) $representative->id;
            $debug['supplier_url'] = (string) $representative->supplier_url;
        }

        $models = $repairs
            ->map(fn (Repair $r) => $this->formatBrandModel((string) $r->brand, (string) $r->model))
            ->filter()
            ->unique()
            ->values();

        $ctx = [
            'category' => (string) ($representative->category ?? ''),
            'models' => $models,
            'current_problem' => (string) ($representative->problem ?? ''),
            'supplier_ref' => (string) $supplierRef,
            'supplier_url' => (string) $representative->supplier_url,
            'supplier_title' => (string) ($supplierData['title'] ?? ''),
            'supplier_sku' => (string) ($supplierData['sku'] ?? ''),
            'supplier_description' => (string) ($supplierData['description'] ?? ''),
            'supplier_product_description' => (string) ($supplierData['product_description'] ?? ''),
            'supplier_text' => (string) ($supplierData['text'] ?? ''),
        ];

        if (!$dryRun && $persistPayload) {
            $now = now();
            Product::query()
                ->where('supplier_reference', $supplierRef)
                ->update([
                    'rhc_last_payload' => $this->encodeJson([
                        'stage' => 'scraped',
                        'supplier_ref' => $supplierRef,
                        'supplier_url' => (string) $representative->supplier_url,
                        'supplier' => [
                            'title' => $this->limitText((string) ($supplierData['title'] ?? ''), 600),
                            'sku' => $this->limitText((string) ($supplierData['sku'] ?? ''), 200),
                            'meta_description' => $this->limitText((string) ($supplierData['description'] ?? ''), 1200),
                            'product_description' => $this->limitText((string) ($supplierData['product_description'] ?? ''), 3000),
                        ],
                        'at' => $now->toISOString(),
                    ]),
                ]);
        }

        $descriptionMode = (string) config('rhcatalogo.description_mode', 'template');

        // Objectif: libellé FR via LLM, description uniforme via template (par défaut).
        $rawGeneratedLabel = '';
        $generatedLabel = '';
        $rawGeneratedDescription = '';
        $generatedDescription = '';

        if ($descriptionMode === 'llm') {
            $generated = $debugEnabled
                ? $this->generatePieceAndDescription($ctx, $debug)
                : $this->generatePieceAndDescription($ctx);
            $rawGeneratedLabel = (string) ($generated['piece_label'] ?? '');
            $rawGeneratedDescription = (string) ($generated['description'] ?? '');
            $generatedLabel = $this->normalizePieceLabel($rawGeneratedLabel);
            $generatedDescription = $this->normalizeDescription($rawGeneratedDescription);
        } else {
            $secondary = $capture
                ? $this->generatePieceLabelOnly($ctx, $debug)
                : $this->generatePieceLabelOnly($ctx);
            $rawGeneratedLabel = (string) ($secondary['piece_label'] ?? '');
            $generatedLabel = $this->normalizePieceLabel($rawGeneratedLabel);
        }

        $rawSecondaryLabel = '';
        $secondaryLabel = '';
        $fallbackLabel = $this->fallbackPieceLabel($ctx);
        $fallbackLabelFr = $this->fallbackFrenchPieceLabel($ctx);

        $pieceLabel = $generatedLabel !== '' ? $generatedLabel : $fallbackLabel;
        $pieceLabel = $this->normalizePieceLabel($this->stripDeviceMentionsFromPieceLabel($ctx, $pieceLabel));
        if ($pieceLabel === '') {
            $pieceLabel = $fallbackLabelFr;
        }

        $requireLlmLabel = (bool) config('rhcatalogo.require_llm_label', false);
        if ($requireLlmLabel && config('rhcatalogo.driver') === 'openai' && $generatedLabel === '') {
            throw new \RuntimeException('LLM label required but empty');
        }

        $labelValidation = (bool) config('rhcatalogo.label_validation', true);
        $plausible = $labelValidation ? $this->isPieceLabelPlausible($ctx, $pieceLabel) : true;
        $usedFallbackFr = false;
        if ($labelValidation && !$plausible) {
            $pieceLabel = $this->normalizePieceLabel($this->stripDeviceMentionsFromPieceLabel($ctx, $fallbackLabelFr));
            $usedFallbackFr = true;
        }

        $fallbackDescription = $this->buildTemplateDescription($ctx, $pieceLabel, $representative);
        $description = ($descriptionMode === 'llm' && $generatedDescription !== '') ? $generatedDescription : $fallbackDescription;

        if ($capture) {
            $pieceLabelSource = $usedFallbackFr
                ? 'fallback_fr'
                : ($generatedLabel !== '' ? ($descriptionMode === 'llm' ? 'openai' : 'openai_secondary') : 'fallback');
            $debug['decision'] = [
                'description_mode' => $descriptionMode,
                'label_validation' => $labelValidation,
                'require_llm_label' => $requireLlmLabel,
                'piece_label_source' => $pieceLabelSource,
                'piece_label_plausible' => $plausible,
                'piece_label_llm_raw' => $rawGeneratedLabel,
                'piece_label_llm_normalized' => $generatedLabel,
                'piece_label_openai_raw' => $rawGeneratedLabel,
                'piece_label_openai_normalized' => $generatedLabel,
                'piece_label_openai_secondary_raw' => $rawSecondaryLabel,
                'piece_label_openai_secondary_normalized' => $secondaryLabel,
                'piece_label_fallback' => $fallbackLabel,
                'piece_label_fallback_fr' => $fallbackLabelFr,
                'piece_label_final' => $pieceLabel,
                'description_source' => ($descriptionMode === 'llm' && $generatedDescription !== '') ? 'openai' : 'template',
                'description_openai_raw' => $rawGeneratedDescription,
                'description_openai_normalized' => $generatedDescription,
                'description_fallback' => $fallbackDescription,
                'description_final_len' => Str::length($description),
            ];
        }

        if ($dryRun) {
            $result = [
                'supplier_ref' => (string) $supplierRef,
                'piece_label' => $pieceLabel,
                'description' => $description,
                'repairs_updated' => 0,
                'products_updated' => 0,
            ];
            if ($debugEnabled) {
                $result['debug'] = $debug;
            }
            return $result;
        }

        $now = CarbonImmutable::now();

        if ($resetProducts) {
            Product::query()
                ->where('supplier_reference', $supplierRef)
                ->update([
                    'rhc_status' => 'pending',
                    'rhc_attempts' => 0,
                    'rhc_last_run_at' => null,
                    'rhc_generated_title' => null,
                    'rhc_generated_description' => null,
                    'rhc_last_error' => null,
                ]);
        }

        $repairsUpdated = 0;
        $productsUpdated = 0;

        foreach ($repairsToUpdate as $repair) {
            $repair->problem = $pieceLabel;
            $repair->update_done = true;
            $repair->save();
            $repairsUpdated++;

            $variant = $repair->ensureVariant();
            $product = $variant->product;

            $title = $this->buildProductTitle($repair, $pieceLabel);

            $product->update([
                'name' => $title,
                'description' => $description,
                'rhc_generated_title' => $title,
                'rhc_generated_description' => $description,
                'rhc_status' => 'ok',
                'rhc_last_run_at' => $now,
                'rhc_last_error' => null,
                'rhc_last_payload' => $persistPayload ? [
                    'stage' => 'done',
                    'supplier_ref' => $supplierRef,
                    'supplier_url' => (string) $representative->supplier_url,
                    'supplier' => [
                        'title' => $this->limitText((string) ($supplierData['title'] ?? ''), 600),
                        'sku' => $this->limitText((string) ($supplierData['sku'] ?? ''), 200),
                        'meta_description' => $this->limitText((string) ($supplierData['description'] ?? ''), 1200),
                        'product_description' => $this->limitText((string) ($supplierData['product_description'] ?? ''), 3000),
                    ],
                    'llm' => $capture ? [
                        'openai' => isset($debug['openai']) && is_array($debug['openai']) ? array_merge($debug['openai'], [
                            'prompt' => $this->limitText((string) ($debug['openai']['prompt'] ?? ''), 6000),
                            'content' => $this->limitText((string) ($debug['openai']['content'] ?? ''), 6000),
                            'response_body' => $this->limitText((string) ($debug['openai']['response_body'] ?? ''), 4000),
                        ]) : null,
                        'openai_secondary' => isset($debug['openai_secondary']) && is_array($debug['openai_secondary']) ? array_merge($debug['openai_secondary'], [
                            'prompt' => $this->limitText((string) ($debug['openai_secondary']['prompt'] ?? ''), 6000),
                            'content' => $this->limitText((string) ($debug['openai_secondary']['content'] ?? ''), 6000),
                            'response_body' => $this->limitText((string) ($debug['openai_secondary']['response_body'] ?? ''), 4000),
                        ]) : null,
                    ] : null,
                    'decision' => $capture ? ($debug['decision'] ?? null) : null,
                    'at' => $now->toISOString(),
                ] : null,
            ]);
            $product->increment('rhc_attempts');
            $productsUpdated++;
        }

        $result = [
            'supplier_ref' => (string) $supplierRef,
            'piece_label' => $pieceLabel,
            'description' => $description,
            'repairs_updated' => $repairsUpdated,
            'products_updated' => $productsUpdated,
        ];
        if ($debugEnabled) {
            $result['debug'] = $debug;
        }
        return $result;
    }

    /**
     * Scrape basique fournisseur (MobileSentrix / Magento):
     * - H1 (copy-text)
     * - JSON-LD Product (name/sku/description/image)
     * - Tabs `product_tabs_*_tabbed_contents` (texte additionnel)
     *
     * @return array{title?:string,sku?:string,description?:string,product_description?:string,image?:string,text?:string}
     */
    /**
     * @return array{
     *   data:array{title?:string,sku?:string,description?:string,product_description?:string,image?:string,text?:string},
     *   debug?:array<string,mixed>,
     *   html_snippet?:string
     * }
     */
    private function fetchSupplierData(string $url, bool $debugEnabled = false, bool $debugHtml = false): array
    {
        $debug = $debugEnabled ? ['requested_url' => $url] : [];

        try {
            $resp = Http::retry(2, 250)
                ->timeout(20)
                ->withOptions([
                    'allow_redirects' => true,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9,fr-FR;q=0.8,fr;q=0.7',
                ])
                ->get($url);

            if ($debugEnabled) {
                $debug['http_status'] = $resp->status();
                $debug['content_type'] = (string) ($resp->header('Content-Type') ?? '');
                $stats = [];
                try {
                    $stats = $resp->handlerStats();
                } catch (\Throwable $e) {
                    $stats = [];
                }
                if (is_array($stats) && isset($stats['url']) && is_string($stats['url'])) {
                    $debug['effective_url'] = $stats['url'];
                }
            }

            if (!$resp->ok()) {
                return $debugEnabled ? ['data' => [], 'debug' => $debug] : ['data' => []];
            }

            $html = $resp->body();
            $title = '';
            $description = '';
            $productDescription = '';
            $sku = '';
            $image = '';
            $textParts = [];
            $signals = [
                'has_copy_text' => Str::contains($html, 'copy-text'),
                'has_product_tabs' => Str::contains($html, 'product_tabs_'),
                'has_jsonld' => Str::contains($html, 'application/ld+json'),
            ];

            $htmlSnippet = null;
            if ($debugEnabled) {
                $debug['body_bytes'] = strlen($html);
                $debug['signals'] = $signals;
                if ($debugHtml) {
                    $htmlSnippet = Str::limit($html, 4000, '...');
                }
            }

            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            $jsonLdNodes = $xpath->query("//script[@type='application/ld+json']");
            if ($jsonLdNodes && $jsonLdNodes->length > 0) {
                foreach ($jsonLdNodes as $node) {
                    $raw = trim((string) $node->textContent);
                    if ($raw === '') {
                        continue;
                    }

                    $raw = preg_replace('/^\\s*\\/\\/<!\\[CDATA\\[\\s*/', '', $raw) ?? $raw;
                    $raw = preg_replace('/\\s*\\/\\/\\]\\]>\\s*$/', '', $raw) ?? $raw;

                    $decoded = json_decode($raw, true);
                    if (!is_array($decoded)) {
                        continue;
                    }

                    $candidates = [];
                    if (($decoded['@type'] ?? null) !== null || ($decoded['name'] ?? null) !== null) {
                        $candidates[] = $decoded;
                    }
                    if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
                        foreach ($decoded['@graph'] as $n) {
                            if (is_array($n)) {
                                $candidates[] = $n;
                            }
                        }
                    }

                    foreach ($candidates as $cand) {
                        $type = $cand['@type'] ?? null;
                        $isProduct = $type === 'Product' || (is_array($type) && in_array('Product', $type, true));
                        if (!$isProduct) {
                            continue;
                        }

                        $title = $title ?: trim((string) ($cand['name'] ?? ''));
                        $sku = $sku ?: trim((string) ($cand['sku'] ?? ''));
                        $description = $description ?: trim((string) ($cand['description'] ?? ''));

                        if (isset($cand['image'])) {
                            if (is_string($cand['image'])) {
                                $image = $image ?: trim($cand['image']);
                            } elseif (is_array($cand['image'])) {
                                $image = $image ?: trim((string) ($cand['image']['image'] ?? $cand['image']['url'] ?? ''));
                            }
                        }
                    }
                }
            }

            $h1Copy = $xpath->query("//h1//span[contains(concat(' ', normalize-space(@class), ' '), ' copy-text ')]");
            if ($h1Copy && $h1Copy->length > 0) {
                $title = trim($h1Copy->item(0)->textContent) ?: $title;
            } else {
                $anyCopy = $xpath->query("//span[contains(concat(' ', normalize-space(@class), ' '), ' copy-text ')]");
                if ($anyCopy && $anyCopy->length > 0) {
                    $title = trim($anyCopy->item(0)->textContent) ?: $title;
                }

                $h1 = $xpath->query('//h1');
                if ($h1 && $h1->length > 0) {
                    $title = trim($h1->item(0)->textContent) ?: $title;
                }
            }

            $metaDesc = $xpath->query("//meta[@name='description']");
            if ($metaDesc && $metaDesc->length > 0) {
                /** @var \DOMElement $node */
                $node = $metaDesc->item(0);
                $description = $description ?: trim((string) $node->getAttribute('content'));
            }

            // Description tab (MobileSentrix): #product_tabs_description_tabbed_contents
            $descTab = $xpath->query("//div[@id='product_tabs_description_tabbed_contents' or @id='product_tabs_description_contents']");
            if ($descTab && $descTab->length > 0) {
                $productDescription = $this->cleanText($descTab->item(0)->textContent);
            }

            $tabNodes = $xpath->query("//div[starts-with(@id,'product_tabs_') and contains(@id,'_tabbed_contents')]");
            if ($tabNodes && $tabNodes->length > 0) {
                foreach ($tabNodes as $tab) {
                    $t = $this->cleanText($tab->textContent);
                    if ($t !== '') {
                        $textParts[] = $t;
                    }
                    if (count($textParts) >= 4) {
                        break;
                    }
                }
            }

            $text = $this->cleanText(implode(' ', $textParts));

            $data = array_filter([
                'title' => $title ?: null,
                'sku' => $sku ?: null,
                'description' => $description ?: null,
                'product_description' => $productDescription ?: null,
                'image' => $image ?: null,
                'text' => $text ?: null,
            ]);

            $result = ['data' => $data];
            if ($debugEnabled) {
                $signals['title_nonempty'] = $title !== '';
                $signals['meta_description_nonempty'] = $description !== '';
                $signals['product_description_nonempty'] = $productDescription !== '';
                $signals['sku_nonempty'] = $sku !== '';
                $signals['text_nonempty'] = $text !== '';
                $debug['signals'] = $signals;
                $result['debug'] = $debug;
                if ($debugHtml && is_string($htmlSnippet)) {
                    $result['html_snippet'] = $htmlSnippet;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('[RHCatalogo] fetchSupplierData failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            if ($debugEnabled) {
                $debug['exception'] = $e->getMessage();
                return ['data' => [], 'debug' => $debug];
            }
            return ['data' => []];
        }
    }

    /**
     * @param array{category:string,models:Collection<int,string>,current_problem:string,supplier_ref:string,supplier_url:string,supplier_title:string,supplier_sku:string,supplier_description:string,supplier_product_description:string,supplier_text:string} $ctx
     * @return array{piece_label?:string,description?:string}
     */
    private function generatePieceAndDescription(array $ctx, ?array &$debug = null): array
    {
        if (config('rhcatalogo.driver') !== 'openai') {
            if ($debug !== null) {
                $debug['openai'] = [
                    'enabled' => false,
                    'reason' => 'driver_not_openai',
                    'driver' => config('rhcatalogo.driver'),
                ];
            }
            return [];
        }

        $endpoint = (string) config('rhcatalogo.openai.endpoint');
        $apiKey = (string) config('rhcatalogo.openai.key');
        $model = (string) config('rhcatalogo.openai.model', 'gpt-4o-mini');
        $timeout = (int) config('rhcatalogo.openai.timeout', 20);

        if ($endpoint === '' || $apiKey === '') {
            if ($debug !== null) {
                $debug['openai'] = [
                    'enabled' => false,
                    'reason' => $endpoint === '' ? 'missing_endpoint' : 'missing_api_key',
                    'driver' => config('rhcatalogo.driver'),
                ];
            }
            return [];
        }

        $prompt = $this->buildOpenAiPromptV2($ctx);
        if ($debug !== null) {
            $debug['openai'] = [
                'enabled' => true,
                'endpoint' => $endpoint,
                'model' => $model,
                'prompt' => $prompt,
            ];
        }

        try {
            [$resp, $meta] = $this->postOpenAiWithRetry($endpoint, $apiKey, $timeout, [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un assistant e-commerce FR. Tu ne DOIS PAS inventer de compatibilités ni de specs. Tu te bases sur le titre et la section \"Product Description\" du fournisseur. Réponds en JSON strict uniquement.",
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.0,
                    'max_tokens' => 550,
                ]);

            if (!$resp->ok()) {
                if ($debug !== null) {
                    $debug['openai']['http_status'] = $resp->status();
                    $debug['openai']['response_body'] = $resp->body();
                    $debug['openai']['error_type'] = $meta['error_type'] ?? null;
                    $debug['openai']['error_code'] = $meta['error_code'] ?? null;
                    $debug['openai']['retryable'] = (bool) ($meta['retryable'] ?? false);
                }
                $status = $resp->status();
                if ($this->shouldPauseOnLlmUnavailable() && in_array($status, [502, 503, 504], true)) {
                    throw new LlmUnavailableException("LLM HTTP {$status}", $endpoint, $status);
                }
                throw new \RuntimeException("OpenAI HTTP {$status}");
            }

            $payload = $resp->json();
            if (!is_array($payload)) {
                throw new \RuntimeException('OpenAI invalid JSON payload');
            }

            $content = (string) ($payload['choices'][0]['message']['content'] ?? '');
            if ($debug !== null) {
                $debug['openai']['http_status'] = $resp->status();
                $debug['openai']['content'] = $content;
                if (isset($payload['usage']) && is_array($payload['usage'])) {
                    $debug['openai']['usage'] = $payload['usage'];
                }
            }
            $decoded = $this->decodeJsonFromModelOutput($content);
            if (!$decoded) {
                throw new \RuntimeException('OpenAI content not JSON');
            }
            if ($debug !== null) {
                $debug['openai']['decoded'] = $decoded;
            }

            return array_filter([
                'piece_label' => isset($decoded['piece_label']) ? trim((string) $decoded['piece_label']) : null,
                'description' => isset($decoded['description']) ? trim((string) $decoded['description']) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[RHCatalogo] OpenAI generation failed', [
                'supplier_ref' => $ctx['supplier_ref'] ?? null,
                'error' => $e->getMessage(),
            ]);
            if ($debug !== null) {
                $debug['openai']['error'] = $e->getMessage();
            }

            if ($this->shouldPauseOnLlmUnavailable() && $this->isLlmUnavailableThrowable($e)) {
                if ($e instanceof LlmUnavailableException) {
                    throw $e;
                }
                throw new LlmUnavailableException($e->getMessage(), $endpoint, null, $e);
            }

            return [];
        }
    }

    /**
     * Fallback LLM : génération uniquement du libellé FR, sans description (moins de risque de dérive).
     *
     * @param array{category:string,models:Collection<int,string>,supplier_ref:string,supplier_url:string,supplier_title:string,supplier_sku:string,supplier_description:string,supplier_product_description:string,supplier_text:string} $ctx
     * @return array{piece_label?:string}
     */
    private function generatePieceLabelOnly(array $ctx, ?array &$debug = null): array
    {
        if (config('rhcatalogo.driver') !== 'openai') {
            if ($debug !== null) {
                $debug['openai_secondary'] = [
                    'enabled' => false,
                    'reason' => 'driver_not_openai',
                    'driver' => config('rhcatalogo.driver'),
                ];
            }
            return [];
        }

        $endpoint = (string) config('rhcatalogo.openai.endpoint');
        $apiKey = (string) config('rhcatalogo.openai.key');
        $model = (string) config('rhcatalogo.openai.model', 'gpt-4o-mini');
        $timeout = (int) config('rhcatalogo.openai.timeout', 20);

        if ($endpoint === '' || $apiKey === '') {
            if ($debug !== null) {
                $debug['openai_secondary'] = [
                    'enabled' => false,
                    'reason' => $endpoint === '' ? 'missing_endpoint' : 'missing_api_key',
                    'driver' => config('rhcatalogo.driver'),
                ];
            }
            return [];
        }

        $prompt = $this->buildOpenAiPieceLabelPromptV5($ctx);
        if ($debug !== null) {
            $debug['openai_secondary'] = [
                'enabled' => true,
                'endpoint' => $endpoint,
                'model' => $model,
                'prompt' => $prompt,
            ];
        }

        try {
            [$resp, $meta] = $this->postOpenAiWithRetry($endpoint, $apiKey, $timeout, [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un assistant e-commerce FR. Tu traduis et reformules en français. Tu ne DOIS PAS inventer de compatibilités ni de specs. Réponds en JSON strict uniquement.",
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.0,
                    'max_tokens' => 120,
                ]);

            if (!$resp->ok()) {
                if ($debug !== null) {
                    $debug['openai_secondary']['http_status'] = $resp->status();
                    $debug['openai_secondary']['response_body'] = $resp->body();
                    $debug['openai_secondary']['error_type'] = $meta['error_type'] ?? null;
                    $debug['openai_secondary']['error_code'] = $meta['error_code'] ?? null;
                    $debug['openai_secondary']['retryable'] = (bool) ($meta['retryable'] ?? false);
                }
                $status = $resp->status();
                if ($this->shouldPauseOnLlmUnavailable() && in_array($status, [502, 503, 504], true)) {
                    throw new LlmUnavailableException("LLM HTTP {$status}", $endpoint, $status);
                }
                throw new \RuntimeException("OpenAI HTTP {$status}");
            }

            $payload = $resp->json();
            if (!is_array($payload)) {
                throw new \RuntimeException('OpenAI invalid JSON payload');
            }

            $content = (string) ($payload['choices'][0]['message']['content'] ?? '');
            if ($debug !== null) {
                $debug['openai_secondary']['http_status'] = $resp->status();
                $debug['openai_secondary']['content'] = $content;
                if (isset($payload['usage']) && is_array($payload['usage'])) {
                    $debug['openai_secondary']['usage'] = $payload['usage'];
                }
            }

            $decoded = $this->decodeJsonFromModelOutput($content);
            if (!$decoded) {
                throw new \RuntimeException('OpenAI content not JSON');
            }
            if ($debug !== null) {
                $debug['openai_secondary']['decoded'] = $decoded;
            }

            return array_filter([
                'piece_label' => isset($decoded['piece_label']) ? trim((string) $decoded['piece_label']) : null,
            ]);
        } catch (\Throwable $e) {
            if ($debug !== null) {
                $debug['openai_secondary']['error'] = $e->getMessage();
            }

            if ($this->shouldPauseOnLlmUnavailable() && $this->isLlmUnavailableThrowable($e)) {
                if ($e instanceof LlmUnavailableException) {
                    throw $e;
                }
                throw new LlmUnavailableException($e->getMessage(), $endpoint, null, $e);
            }
            return [];
        }
    }

    /**
     * @param array{category:string,models:Collection<int,string>,current_problem:string,supplier_ref:string,supplier_url:string,supplier_title:string,supplier_sku:string,supplier_description:string,supplier_product_description:string,supplier_text:string} $ctx
     */
    private function buildOpenAiPrompt(array $ctx): string
    {
        $models = $ctx['models']->take(12)->implode(', ');

        $qty = $this->detectQuantityHint($ctx);
        $qtyHint = $qty !== null ? (string) $qty : '';

        $prompt = <<<PROMPT
Contexte interne :
- Catégorie : {$ctx['category']}
- Modèles (peut être multiple) : {$models}

Source fournisseur (vérité) :
- supplier_ref/SKU : {$ctx['supplier_ref']}
- URL : {$ctx['supplier_url']}
- Titre fournisseur (H1) : {$ctx['supplier_title']}
- Onglet "Product Description" : {$ctx['supplier_product_description']}
- Meta description : {$ctx['supplier_description']}
- Autres textes : {$ctx['supplier_text']}

Objectif :
1) Donne le libellé exact et précis de la PIÈCE en français (pas la réparation).
   - Base-toi d'abord sur le H1 + "Product Description".
   - Si la source fournisseur est en anglais, traduis-la en français.
   - N'inclus pas le nom de l'appareil/marque/modèle dans le libellé : uniquement la pièce.
   - Exemple attendu : si c'est "Battery FPC ..." alors ce n'est PAS "Batterie" mais "Connecteur batterie (FPC)".
   - Si un pack est mentionné (ex: "10 Pack"), intègre-le ("lot de 10").
2) Rédige une description FR (140–220 mots) rassurante et factuelle, alignée avec la pièce réelle.

Contraintes :
- N'invente aucune compatibilité au-delà de ce qui est visible sur la page fournisseur ou dans la liste de modèles.
- Pas de prix. Pas de promo. Pas de markdown.
- Réponds UNIQUEMENT en JSON strict :
  {"piece_label":"...","description":"..."}
PROMPT;

        return trim($prompt);
    }

    /**
     * @param array{supplier_ref:string,supplier_url:string,supplier_title:string,supplier_description:string,supplier_product_description:string} $ctx
     */
    private function buildOpenAiPieceLabelPrompt(array $ctx): string
    {
        $qty = $this->detectQuantityHint($ctx);
        $qtyHint = $qty !== null ? (string) $qty : '';

        $prompt = <<<PROMPT
Source fournisseur (vérité) :
- supplier_ref/SKU : {$ctx['supplier_ref']}
- URL : {$ctx['supplier_url']}
- Titre fournisseur (H1) : {$ctx['supplier_title']}
- Onglet "Product Description" : {$ctx['supplier_product_description']}
- Meta description : {$ctx['supplier_description']}

Indices utiles :
- Quantite detectee (si presente) : {$qtyHint}
- Mini-glossaire (si le terme est present dans le titre/description) :
  - assembly = ensemble / module / ecran assemble (pas "assembly")
  - motor / vibration motor = moteur vibreur
  - screw / screws = vis
  - screw set = kit de vis
  - rear / back = arriere (pas "retro")
  - shell / housing = coque
  - camera lens = lentille camera
  - bracket = support
  - bezel = contour
  - charging port = connecteur de charge
  - battery = batterie

Format attendu (important) :
- Francais uniquement (0 mot anglais).
- 3 a 10 mots, style catalogue : nom de piece + qualificatifs utiles.
- Commence par une majuscule.
- Ne commence jamais par "Back"/"Rear"/"Assembly"/"With".
- N'inclus pas la marque/modele/appareil (ex: pas "Nintendo Switch").
- Si un lot/pack est explicitement mentionne, ajoute " - lot de N".
- Evite les mots vides ("compatible", "for", "replacement").

Exemples (generiques) :
- "Rear Shell Screws ... (100 Pack)" -> "Kit de vis coque arriere (embout Y) - lot de 100"
- "Vibration Motor HD" -> "Moteur vibreur HD"
- "OLED Assembly With Proximity Sensor" -> "Ecran OLED assemble avec capteur de proximite"
- "Back Camera Lens With Bracket & Bezel (3 Pack)" -> "Lentille camera arriere avec support et contour - lot de 3"
- "US Version Rear Housing" -> "Coque arriere (version US)"

Tâche :
- Donne un libellé de PIÈCE en français, court et précis (3-10 mots).
- Traduis les termes anglais si besoin.
- N'inclus pas le nom de l'appareil/marque/modèle.
- Ne devine pas : reste strictement aligné avec le texte fournisseur.
- Si une quantite est indiquee (pack/pieces), utilise le format "lot de X".
- Réponds UNIQUEMENT en JSON strict :
  {"piece_label":"..."}
PROMPT;

        return trim($prompt);
    }

    /**
     * Résout de manière fiable la page fournisseur à utiliser :
     * - essaie plusieurs repairs (même supplier_ref) jusqu'à obtenir un titre OU une description exploitable
     *
     * @param Collection<int,Repair> $repairs
     * @return array{0:Repair,1:array{title?:string,sku?:string,description?:string,product_description?:string,image?:string,text?:string}}
     */
    /**
     * @param Collection<int,Repair> $repairs
     * @param array<string,mixed> $debug
     * @return array{0:Repair,1:array{title?:string,sku?:string,description?:string,product_description?:string,image?:string,text?:string}}
     */
    /**
     * Prompt v2 (refait de zero) : construit ligne par ligne pour etre totalement different
     * des prompts historiques et donner des libelles plus propres.
     *
     * @param array{category:string,models:Collection<int,string>,current_problem:string,supplier_ref:string,supplier_url:string,supplier_title:string,supplier_sku:string,supplier_description:string,supplier_product_description:string,supplier_text:string} $ctx
     */
    private function buildOpenAiPromptV2(array $ctx): string
    {
        $models = $ctx['models']->take(12)->implode(', ');

        $p = [];
        $p[] = 'RHCATALOGO_V2';
        $p[] = 'CONTRAT: reponds UNIQUEMENT par un JSON strict, sans texte autour.';
        $p[] = '';
        $p[] = 'DONNEES (source de verite)';
        $p[] = '- categorie: '.$ctx['category'];
        $p[] = '- modeles: '.$models;
        $p[] = '- reference_fournisseur: '.$ctx['supplier_ref'];
        $p[] = '- url: '.$ctx['supplier_url'];
        $p[] = '- titre_h1: '.$ctx['supplier_title'];
        $p[] = '- description_produit: '.$ctx['supplier_product_description'];
        $p[] = '- meta_description: '.$ctx['supplier_description'];
        $p[] = '- autres_textes: '.$ctx['supplier_text'];
        $p[] = '';
        $p[] = 'A PRODUIRE';
        $p[] = '1) piece_label (obligatoire): nom de la piece en FR, pas la reparation.';
        $p[] = '2) description (obligatoire): texte FR 140-220 mots, factuel et rassurant.';
        $p[] = '';
        $p[] = 'REGLES STRICTES';
        $p[] = '- FR uniquement: aucun mot anglais dans piece_label.';
        $p[] = '- piece_label ne doit pas contenir marque/appareil/modele.';
        $p[] = '- ne pas inventer de compatibilites/specs/prix.';
        $p[] = '- pas de promo, pas de markdown.';
        $p[] = '';
        $p[] = 'FORMAT piece_label';
        $p[] = '- 5 a 14 mots si la source donne assez d infos.';
        $p[] = '- nom + qualificatifs utiles (avant/arriere, OLED/LCD, capteur, version, couleur, embout).';
        $p[] = '- interdit: "flexionnaire" (utilise "cable flex" ou "nappe flex").';
        $p[] = '- interdit: "pour <appareil>" (ex: "pour iPhone 13 Mini"). Supprime le nom de l appareil du titre.';
        $p[] = '- eviter: compatible, remplacement, replacement, for.';
        $p[] = '';
        $p[] = 'SORTIE JSON';
        $p[] = '{"piece_label":"...","description":"..."}';

        return trim(implode("\n", $p));
    }

    /**
     * Prompt v2 (refait de zero) pour libelle uniquement.
     *
     * @param array{supplier_ref:string,supplier_url:string,supplier_title:string,supplier_description:string,supplier_product_description:string} $ctx
     */
    private function buildOpenAiPieceLabelPromptV2(array $ctx): string
    {
        $p = [];
        $p[] = 'RHCATALOGO_LABEL_V2';
        $p[] = 'CONTRAT: un seul JSON strict, aucun autre texte.';
        $p[] = '';
        $p[] = 'SOURCE (verite fournisseur)';
        $p[] = 'reference_fournisseur: '.$ctx['supplier_ref'];
        $p[] = 'url: '.$ctx['supplier_url'];
        $p[] = 'titre_h1: '.$ctx['supplier_title'];
        $p[] = 'description_produit: '.$ctx['supplier_product_description'];
        $p[] = 'meta_description: '.$ctx['supplier_description'];
        $p[] = '';
        $p[] = 'CONTEXTE';
        $p[] = '- Tu nommes une PIECE DETACHEE pour un catalogue e-commerce technique.';
        $p[] = '- Le but est un titre clair, descriptif et precis (pas un titre trop court).';
        $p[] = '';
        $p[] = 'SORTIE ATTENDUE';
        $p[] = '- piece_label: nom technique FR de la piece (pas une action de reparation).';
        $p[] = '';
        $p[] = 'REGLES';
        $p[] = '- FR uniquement: aucun mot anglais.';
        $p[] = '- pas de marque/appareil/modele.';
        $p[] = '- ne pas deviner: seulement ce qui est visible dans SOURCE.';
        $p[] = '- interdit: "flexionnaire" (utilise "cable flex" ou "nappe flex").';
        $p[] = '- interdit: "pour <appareil>" (ex: "pour iPhone 13 Mini"). Supprime le nom de l appareil du titre.';
        $p[] = '- eviter: compatible, remplacement, replacement, for.';
        $p[] = '- longueur visee: 5 a 14 mots (si la SOURCE donne assez d infos).';
        $p[] = '- structure: NOM de piece + qualificatifs utiles (position, type, usage, capteur, version, couleur).';
        $p[] = '- pas de titre trop vague: evite 1 seul mot si la SOURCE contient des precisions.';
        $p[] = '';
        $p[] = 'LEXIQUE DE TRADUCTION (anglais -> francais, seulement si present dans la source)';
        $p[] = '- flex cable / flex -> cable flex / nappe flex (jamais "flexionnaire")';
        $p[] = '- power button -> bouton power / bouton marche-arret';
        $p[] = '- volume button -> bouton volume';
        $p[] = '- adhesive tape / adhesive -> bande adhesive / adhesif';
        $p[] = '- back cover / rear cover -> cache arriere';
        $p[] = '- camera lens -> lentille camera';
        $p[] = '- housing/shell -> coque';
        $p[] = '- screen/oled/lcd -> ecran / OLED / LCD';
        $p[] = '';
        $p[] = 'LEXIQUE DE TRADUCTION (anglais -> francais, uniquement si visible dans SOURCE)';
        $p[] = '- adhesive tape / adhesive -> bande adhésive / adhésif';
        $p[] = '- back cover / rear cover -> cache arrière';
        $p[] = '- vibration motor -> moteur vibreur';
        $p[] = '- rear/back -> arriere (pas "retro")';
        $p[] = '- housing/shell -> coque';
        $p[] = '- screen/oled/lcd -> ecran / OLED / LCD';
        $p[] = '- camera lens -> lentille camera';
        $p[] = '- bracket/bezel -> support/contour';
        $p[] = '- screw set/screws -> kit de vis/vis';
        $p[] = '- assembly -> module / ensemble / ecran assemble';
        $p[] = '';
        $p[] = 'EXEMPLES (anglais -> francais)';
        $p[] = '- Back Cover Adhesive Tape -> Bande adhesive pour cache arriere';
        $p[] = '- OLED Assembly With Proximity Sensor -> Module ecran OLED avec capteur de proximite';
        $p[] = '- Back Camera Lens With Bracket & Bezel -> Lentille camera avec support et contour';
        $p[] = '';
        $p[] = 'EXEMPLES';
        $p[] = '- Back Cover Adhesive Tape -> Bande adhésive pour cache arrière';
        $p[] = '- OLED Assembly With Proximity Sensor -> Module écran OLED avec capteur de proximité';
        $p[] = '- Back Camera Lens With Bracket & Bezel -> Lentille camera avec support et contour';
        $p[] = '';
        $p[] = 'JSON STRICT';
        $p[] = '{"piece_label":"..."}';

        return trim(implode("\n", $p));
    }

    /**
     * Prompt v3 (clean) : 100% francais (hors texte fournisseur), orienté catalogue technique,
     * avec garde-fous explicites contre "pour <appareil>" et "flexionnaire".
     *
     * @param array{supplier_ref:string,supplier_url:string,supplier_title:string,supplier_description:string,supplier_product_description:string} $ctx
     */
    private function buildOpenAiPieceLabelPromptV3(array $ctx): string
    {
        $p = [];
        $p[] = 'RHCATALOGO_LABEL_V3';
        $p[] = 'Regle absolue: reponds UNIQUEMENT par un JSON strict, rien d autre.';
        $p[] = '';
        $p[] = 'SOURCE FOURNISSEUR (verite)';
        $p[] = 'reference_fournisseur: '.$ctx['supplier_ref'];
        $p[] = 'url: '.$ctx['supplier_url'];
        $p[] = 'titre_h1: '.$ctx['supplier_title'];
        $p[] = 'description_produit: '.$ctx['supplier_product_description'];
        $p[] = 'meta_description: '.$ctx['supplier_description'];
        $p[] = '';
        $p[] = 'TACHE';
        $p[] = '- Ecris un libelle de PIECE en francais, technique, clair et precis.';
        $p[] = '';
        $p[] = 'CONTRAINTES';
        $p[] = '- Francais uniquement: aucun mot anglais.';
        $p[] = '- N ecris JAMAIS "pour <appareil>" (ex: "pour iPhone 13 Mini"). Si l appareil est present dans la source, retire-le.';
        $p[] = '- Ne pas inventer de compatibilite/specs: uniquement ce que la source permet.';
        $p[] = '- Interdit: le mot "flexionnaire". A la place: "cable flex" ou "nappe flex".';
        $p[] = '- IMPORTANT: ne confonds pas "ecran/module ecran" avec "nappe/cable flex".';
        $p[] = '  - Si la source contient OLED/LCD/screen/display/assembly/cadre/frame => c est un ecran ou module ecran (pas une nappe), sauf si la source dit explicitement "flex/fpc/cable".';
        $p[] = '  - Si la source dit explicitement flex/fpc/cable => c est une nappe/cable (pas un ecran complet).';
        $p[] = '- Longueur visee: 5 a 14 mots si possible.';
        $p[] = '';
        $p[] = 'STYLE';
        $p[] = '- Commence par une majuscule.';
        $p[] = '- Structure: nom de piece + precisions utiles (position, usage, type, capteur, version, couleur).';
        $p[] = '- Evite les mots faibles: compatible, remplacement.';
        $p[] = '';
        $p[] = 'TRADUCTIONS (anglais -> francais, seulement si visible dans la source)';
        $p[] = '- back cover / rear cover = cache arriere';
        $p[] = '- adhesive tape / adhesive = bande adhesive / adhesif';
        $p[] = '- flex cable / flex = cable flex / nappe flex';
        $p[] = '- power button = bouton power / bouton marche-arret';
        $p[] = '- volume button = bouton volume';
        $p[] = '- camera lens = lentille camera';
        $p[] = '- housing/shell = coque';
        $p[] = '- screen/oled/lcd = ecran / OLED / LCD';
        $p[] = '';
        $p[] = 'EXEMPLES (anglais -> francais)';
        $p[] = '- Back Cover Adhesive Tape -> Bande adhesive pour cache arriere';
        $p[] = '- Power Button Flex Cable -> Cable flex du bouton power';
        $p[] = '- Back Camera Lens With Bracket & Bezel -> Lentille camera avec support et contour';
        $p[] = '';
        $p[] = 'JSON STRICT';
        $p[] = '{"piece_label":"..."}';

        return trim(implode("\n", $p));
    }

    /**
     * Prompt v4 (Qwen2.5-32B) : plus de contexte utile (catégorie/modèles) tout en interdisant
     * de les inclure dans le libellé final, et consignes de désambiguïsation plus strictes.
     *
     * @param array{category?:string,models?:Collection<int,string>,supplier_ref:string,supplier_url:string,supplier_title:string,supplier_description:string,supplier_product_description:string} $ctx
     */
    private function buildOpenAiPieceLabelPromptV4(array $ctx): string
    {
        $models = $ctx['models'] instanceof Collection ? $ctx['models']->take(8)->implode(', ') : '';
        $category = trim((string) ($ctx['category'] ?? ''));

        $p = [];
        $p[] = 'RHCATALOGO_LABEL_V4';
        $p[] = 'IMPORTANT: reponds UNIQUEMENT en JSON strict. Aucun texte autour. Pas de markdown.';
        $p[] = '';
        $p[] = 'CONTEXTE INTERNE (ne jamais recopier dans le libelle)';
        $p[] = '- categorie: '.$category;
        $p[] = '- modeles: '.$models;
        $p[] = '';
        $p[] = 'SOURCE FOURNISSEUR (verite)';
        $p[] = 'reference_fournisseur: '.$ctx['supplier_ref'];
        $p[] = 'url: '.$ctx['supplier_url'];
        $p[] = 'titre_h1: '.$ctx['supplier_title'];
        $p[] = 'description_produit: '.$ctx['supplier_product_description'];
        $p[] = 'meta_description: '.$ctx['supplier_description'];
        $p[] = '';
        $p[] = 'TACHE';
        $p[] = '- Produis un libelle FR technique, clair et precis pour la PIECE (pas une reparation).';
        $p[] = '';
        $p[] = 'CONTRAINTES STRICTES';
        $p[] = '- FR uniquement (aucun mot anglais).';
        $p[] = '- Interdit: marque/appareil/modele (ex: iPhone 13 Mini, Sony PS5, Nintendo Switch).';
        $p[] = '- Interdit: "pour <appareil>".';
        $p[] = '- Interdit: le mot "flexionnaire".';
        $p[] = '- Ne pas inventer de specs/compatibilites/prix.';
        $p[] = '';
        $p[] = 'DESAMBIGUISATION (critique)';
        $p[] = '- Si la source contient: OLED/LCD/screen/display/assembly/module/cadre/frame => c est un ECRAN / MODULE ECRAN (pas une nappe),';
        $p[] = '  sauf si la source contient explicitement: flex/fpc/ribbon/cable.';
        $p[] = '- Si la source contient explicitement: flex/fpc/ribbon/cable => c est une NAPPE FLEX / CABLE FLEX (pas un ecran complet).';
        $p[] = '';
        $p[] = 'STYLE';
        $p[] = '- 6 a 16 mots si possible.';
        $p[] = '- Majuscule au debut.';
        $p[] = '- Structure: nom de piece + precisions utiles (bouton, connecteur, capteur, arriere/avant, avec cadre/support/contour, couleur, version).';
        $p[] = '';
        $p[] = 'LEXIQUE (anglais -> francais, seulement si present dans la source)';
        $p[] = '- back cover / rear cover = cache arriere';
        $p[] = '- frame = cadre';
        $p[] = '- bezel = contour';
        $p[] = '- bracket = support';
        $p[] = '- adhesive tape / adhesive = bande adhesive / adhesif';
        $p[] = '- flex cable / ribbon cable = cable flex / nappe flex';
        $p[] = '- power button = bouton power / bouton marche-arret';
        $p[] = '- volume button = bouton volume';
        $p[] = '- charging port = connecteur de charge';
        $p[] = '- camera lens = lentille camera';
        $p[] = '- housing/shell = coque';
        $p[] = '';
        $p[] = 'EXEMPLES (anglais -> francais)';
        $p[] = '- Back Cover Adhesive Tape -> Bande adhesive pour cache arriere';
        $p[] = '- OLED Assembly With Frame -> Module ecran OLED avec cadre';
        $p[] = '- Power Button Flex Cable -> Cable flex du bouton power';
        $p[] = '';
        $p[] = 'JSON STRICT';
        $p[] = '{"piece_label":"..."}';

        return trim(implode("\n", $p));
    }

    /**
     * Prompt v5 (concis) : titres courts, centrés sur le terme principal.
     * Autorise certains termes techniques non-traduits (ex: "Mid-Frame") quand ils viennent de la source.
     *
     * @param array{category?:string,models?:Collection<int,string>,supplier_ref:string,supplier_url:string,supplier_title:string,supplier_description:string,supplier_product_description:string} $ctx
     */
    private function buildOpenAiPieceLabelPromptV5(array $ctx): string
    {
        $models = $ctx['models'] instanceof Collection ? $ctx['models']->take(6)->implode(', ') : '';
        $category = trim((string) ($ctx['category'] ?? ''));

        $p = [];
        $p[] = 'RHCATALOGO_LABEL_V5';
        $p[] = 'Reponds UNIQUEMENT en JSON strict: {"piece_label":"..."} (aucun texte autour).';
        $p[] = '';
        $p[] = 'CONTEXTE (a ne pas inclure dans le libelle)';
        $p[] = '- categorie: '.$category;
        $p[] = '- modeles: '.$models;
        $p[] = '';
        $p[] = 'SOURCE (verite fournisseur)';
        $p[] = 'reference_fournisseur: '.$ctx['supplier_ref'];
        $p[] = 'url: '.$ctx['supplier_url'];
        $p[] = 'titre_h1: '.$ctx['supplier_title'];
        $p[] = 'description_produit: '.$ctx['supplier_product_description'];
        $p[] = 'meta_description: '.$ctx['supplier_description'];
        $p[] = '';
        $p[] = 'OBJECTIF';
        $p[] = '- Donner un libelle de PIECE simple et qualitatif, centre sur le plus important.';
        $p[] = '';
        $p[] = 'REGLES';
        $p[] = '- Ne jamais inclure marque/appareil/modele (ex: iPhone 13 Mini, Samsung Galaxy A20, Sony PS5).';
        $p[] = '- Ne jamais ecrire "pour <appareil>".';
        $p[] = '- Ne pas inventer de specs/compatibilites.';
        $p[] = '- Court: 2 a 6 mots (max 7 si vraiment necessaire).';
        $p[] = '- Eviter les details secondaires (ex: "avec support preinstalle", "avec cadre").';
        $p[] = '';
        $p[] = 'TERMINOLOGIE';
        $p[] = '- Francais par defaut, MAIS tu peux conserver tel quel un terme technique tres courant s il apparait dans la source :';
        $p[] = '  Mid-Frame, Touchpad, Trackpad, Face ID, TrueDepth.';
        $p[] = '- Ex: "Mid-Frame Housing ..." -> "Mid-Frame".';
        $p[] = '';
        $p[] = 'DESAMBIGUISATION (rapide)';
        $p[] = '- Si la source contient OLED/LCD/screen/display => utiliser "Module ecran OLED" ou "Module ecran LCD" (pas "nappe").';
        $p[] = '- Si la source contient flex/fpc/ribbon/cable => "Nappe flex" ou "Cable flex" + element principal (ex: "bouton power").';
        $p[] = '';
        $p[] = 'EXEMPLES';
        $p[] = '- Mid-Frame Housing For Samsung Galaxy A20 -> Mid-Frame';
        $p[] = '- Back Cover Adhesive Tape -> Bande adhesive cache arriere';
        $p[] = '- LCD Screen Assembly with bracket preinstalled -> Module ecran LCD';
        $p[] = '';
        $p[] = 'JSON STRICT';
        $p[] = '{"piece_label":"..."}';

        return trim(implode("\n", $p));
    }

    private function resolveSupplierPage(Collection $repairs, bool $debugEnabled = false, bool $debugHtml = false, array &$debug = []): array
    {
        $errors = [];
        $attempts = [];

        /** @var Repair $repair */
        foreach ($repairs as $repair) {
            $url = trim((string) $repair->supplier_url);
            if ($url === '') {
                continue;
            }

            $scrape = $this->fetchSupplierData($url, $debugEnabled, $debugHtml);
            $data = $scrape['data'] ?? [];

            if ($debugEnabled) {
                $attempt = is_array($scrape['debug'] ?? null) ? $scrape['debug'] : [];
                $attempt['repair_id'] = (int) $repair->id;
                $attempt['supplier_url'] = $url;
                $attempt['extracted'] = [
                    'title' => (string) ($data['title'] ?? ''),
                    'sku' => (string) ($data['sku'] ?? ''),
                    'description' => (string) ($data['description'] ?? ''),
                    'product_description' => (string) ($data['product_description'] ?? ''),
                    'text' => (string) ($data['text'] ?? ''),
                ];
                $attempts[] = $attempt;
            }

            $hasSignal = !empty($data['title']) || !empty($data['product_description']) || !empty($data['sku']) || !empty($data['description']);
            if ($hasSignal) {
                if ($debugEnabled) {
                    $debug['scrape'] = is_array($scrape['debug'] ?? null) ? $scrape['debug'] : [];
                    $debug['extracted'] = $data;
                    $debug['scrape_attempts'] = $attempts;
                    if ($debugHtml && isset($scrape['html_snippet']) && is_string($scrape['html_snippet'])) {
                        $debug['html_snippet'] = $scrape['html_snippet'];
                    }
                }
                return [$repair, $data];
            }

            $errors[] = "repair#{$repair->id}: empty scrape ({$url})";
        }

        if ($debugEnabled && !empty($attempts)) {
            $debug['scrape_attempts'] = $attempts;
        }

        $msg = $errors ? implode(' | ', array_slice($errors, 0, 3)) : 'no supplier_url available';
        throw new \RuntimeException("Supplier scrape failed: {$msg}");
    }

    /**
     * @return array<string,mixed>|null
     */
    private function decodeJsonFromModelOutput(string $content): ?array
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\\{[\\s\\S]*\\}/', $trimmed, $m) !== 1) {
            return null;
        }

        $decoded = json_decode($m[0], true);
        return is_array($decoded) ? $decoded : null;
    }

    private function normalizePieceLabel(string $label): string
    {
        $label = $this->cleanText($label);
        $label = trim($label, "\"'");
        $label = preg_replace('/\\s{2,}/', ' ', $label) ?? $label;
        // Normalise les notations de pack/lot quand elles apparaissent quand meme dans la sortie LLM.
        $label = preg_replace('/\\s*(?:-|–|—)?\\s*lot\\s+de\\s*(\\d{1,4})\\b/i', ' x$1', $label) ?? $label;
        $label = preg_replace('/\\s{2,}/', ' ', $label) ?? $label;
        $label = trim($label);

        if (Str::length($label) < 3) {
            return '';
        }

        $label = Str::ucfirst($label);
        return Str::limit($label, 120, '');
    }

    private function normalizeDescription(string $description): string
    {
        $description = $this->cleanText($description);
        return Str::length($description) < 80 ? '' : $description;
    }

    /**
     * @param array{supplier_title:string,supplier_description:string,supplier_text:string,current_problem?:string} $ctx
     */
    private function fallbackPieceLabel(array $ctx): string
    {
        $supplierTitle = trim((string) ($ctx['supplier_title'] ?? ''));
        $supplierProductDescription = trim((string) ($ctx['supplier_product_description'] ?? ''));

        $source = $supplierTitle !== '' ? $supplierTitle : $supplierProductDescription;
        $source = $this->cleanText($source);

        if ($source === '') {
            return $ctx['current_problem'] ?? 'Piece detachee';
        }

        // Fallback non-heuristique : réutilise le libellé fournisseur (sans tenter de le "classifier").
        // Le LLM est responsable de la traduction/normalisation quand il est disponible.
        $label = $source;

        // Nettoyage léger et générique : enlève les répétitions de "Compatible for".
        $label = preg_replace('/\\bcompatible\\s+for\\b\\s*/i', '', $label) ?? $label;
        $label = preg_replace('/\\s{2,}/', ' ', $label) ?? $label;
        $label = trim($label, " \t\n\r\0\x0B-–—");

        return Str::limit($label, 110, '');
    }

    /**
     * @param array{models:Collection<int,string>,supplier_title:string,supplier_description:string} $ctx
     */
    private function fallbackDescription(array $ctx, string $pieceLabel): string
    {
        $models = $ctx['models']->take(6)->implode(', ');
        $source = trim((string) ($ctx['supplier_title'] ?? ''));
        if (($ctx['supplier_description'] ?? '') !== '') {
            $source .= ' - '.trim((string) $ctx['supplier_description']);
        }

        $sentences = [
            "{$pieceLabel}: piece de remplacement pour {$models}.",
            $source !== '' ? "Source fournisseur: {$source}." : null,
            "Cette piece est destinee a la reparation/remise en etat de votre appareil.",
            "Conseil: faites poser par un professionnel (micro-soudure ou demontage selon la piece).",
            "Compatibilite: verifiez toujours le modele exact avant commande.",
        ];

        return $this->cleanText(implode(' ', array_filter($sentences)));
    }

    /**
     * Description uniforme (template), sans inventer de specs.
     *
     * @param array{models:Collection<int,string>} $ctx
     */
    private function buildTemplateDescription(array $ctx, string $pieceLabel, Repair $representative): string
    {
        $models = $ctx['models']->take(8)->implode(', ');
        $quality = $this->qualityLabelFromRepair($representative);

        $p1 = "{$pieceLabel} : pièce de remplacement pour {$models}.";
        $p2 = "Cette pièce est destinée à remplacer un élément endommagé ou usé lors d'une réparation.";
        $p3 = "Avant commande, comparez la forme, les connecteurs et la version de votre appareil afin d'éviter toute erreur.";
        $p4 = "Montage : démontage nécessaire. Selon la pièce, des outils adaptés et une bonne expérience sont recommandés ; en cas de doute, confiez la pose à un professionnel.";
        $p5 = "Compatibilité : vérifiez toujours le modèle exact ({$models}) ainsi que les éventuelles variantes. Type : {$quality}.";

        return $this->cleanText(implode(' ', [$p1, $p2, $p3, $p4, $p5]));
    }

    private function buildProductTitle(Repair $repair, string $pieceLabel): string
    {
        $category = trim((string) $repair->category);
        $brandModel = $this->formatBrandModel((string) $repair->brand, (string) $repair->model);
        $quality = $this->qualityLabelFromRepair($repair);
        $color = trim((string) $repair->color);

        $tail = [];
        if ($color !== '' && !Str::contains(Str::lower($color), 'standard')) {
            $tail[] = $color;
        }
        if ($quality !== '') {
            $tail[] = $quality;
        }

        $base = implode(' - ', array_filter([$category, $brandModel, $pieceLabel]));
        if (!empty($tail)) {
            $base .= ' | '.implode(' | ', $tail);
        }

        return Str::limit($base, 110, '');
    }

    private function formatBrandModel(string $brand, string $model): string
    {
        $brand = trim($brand);
        $model = trim($model);

        if ($brand === '') {
            return $model;
        }
        if ($model === '') {
            return $brand;
        }

        return Str::startsWith(Str::lower($model), Str::lower($brand))
            ? $model
            : trim($brand.' '.$model);
    }

    private function qualityLabelFromRepair(Repair $repair): string
    {
        $token = Str::lower(trim((string) $repair->component_brand));
        if (Str::contains($token, ['oem', 'orig', 'original', 'origine', 'genuine'])) {
            return 'Origine';
        }
        return 'Compatible';
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/\\s+/u', ' ', $text) ?? $text;
        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
    }
}
