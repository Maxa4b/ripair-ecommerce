<?php

namespace App\Console\Commands;

use App\Models\Legacy\Repair;
use App\Services\RHCatalogo\Exceptions\LlmUnavailableException;
use App\Services\RHCatalogo\RHCatalogoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RHCatalogoRun extends Command
{
    protected $signature = 'rhcatalogo:run
        {--supplier-ref= : Ne traiter que ce supplier_ref}
        {--limit=50 : Nombre de supplier_ref à traiter (0 = tous)}
        {--offset=0 : Décalage dans la liste}
        {--sample : Prend un échantillon aléatoire (ignore l\'ordre)}
        {--include-done : Inclut aussi les repairs déjà update_done}
        {--reset-products : Reset les champs RHC des products ciblés}
        {--flush-queue : Vide la queue (jobs/failed_jobs) si driver=database}
        {--dry-run : N\'écrit rien (scrape + IA seulement)}
        {--debug : Affiche les variables (scrape + prompt + sortie IA)}
        {--debug-html : Inclut un extrait HTML (utile si le scrape est vide)}';

    protected $description = 'Pipeline unique RHCATALOGO (scrape fournisseur + IA + sync repairs/products)';

    public function handle(RHCatalogoService $service): int
    {
        $onlySupplierRef = trim((string) $this->option('supplier-ref'));
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $sample = (bool) $this->option('sample');
        $includeDone = (bool) $this->option('include-done');
        $resetProducts = (bool) $this->option('reset-products');
        $flushQueue = (bool) $this->option('flush-queue');
        $dryRun = (bool) $this->option('dry-run');
        $debug = (bool) $this->option('debug');
        $debugHtml = (bool) $this->option('debug-html');

        if ($flushQueue) {
            $this->flushQueueTables();
        }

        $query = Repair::query()
            ->selectRaw('supplier_ref, MAX(id) as max_id')
            ->whereNotNull('supplier_ref')
            ->whereNotNull('supplier_url')
            ->when(!$includeDone, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('update_done')->orWhere('update_done', false);
                });
            })
            ->groupBy('supplier_ref')
            ->when(!$sample, fn ($q) => $q->orderByDesc('max_id'))
            ->when($sample, fn ($q) => $q->inRandomOrder());

        if ($onlySupplierRef !== '') {
            $query->where('supplier_ref', $onlySupplierRef);
        }

        if ($offset > 0) {
            $query->offset($offset);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $refs = $query->get();
        if ($refs->isEmpty()) {
            $this->warn('Aucun supplier_ref trouvé (avec supplier_url).');
            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;

        foreach ($refs as $row) {
            $supplierRef = (string) $row->supplier_ref;
            $this->line("==> {$supplierRef}");

            try {
                while (true) {
                    try {
                        $result = $service->processSupplierRef($supplierRef, [
                            'dry_run' => $dryRun,
                            'reset_products' => $resetProducts,
                            'debug' => $debug,
                            'debug_html' => $debugHtml,
                            'include_done' => $includeDone,
                        ]);
                        break;
                    } catch (LlmUnavailableException $e) {
                        $endpoint = $e->endpoint ?: (string) config('rhcatalogo.openai.endpoint');
                        $this->warn("LLM indisponible ({$endpoint}). Pause en attendant le retour de la connexion...");
                        $service->waitForLlmAvailability($endpoint);
                        $this->info('LLM OK, reprise...');
                    }
                }

                if ($debug && isset($result['debug']) && is_array($result['debug'])) {
                    $this->newLine();
                    $this->line('--- DEBUG ---');
                    $this->line('supplier_ref: '.(string) ($result['debug']['supplier_ref'] ?? $supplierRef));
                    if (($result['debug']['representative_repair_id'] ?? null) !== null) {
                        $this->line('representative_repair_id: '.(string) $result['debug']['representative_repair_id']);
                    }
                    $this->line('supplier_url: '.(string) ($result['debug']['supplier_url'] ?? ''));

                    if (isset($result['debug']['scrape']) && is_array($result['debug']['scrape'])) {
                        $scrape = $result['debug']['scrape'];
                        if (isset($scrape['http_status'])) {
                            $this->line('scrape.http_status: '.(string) $scrape['http_status']);
                        }
                        if (($scrape['content_type'] ?? '') !== '') {
                            $this->line('scrape.content_type: '.(string) $scrape['content_type']);
                        }
                        if (isset($scrape['body_bytes'])) {
                            $this->line('scrape.body_bytes: '.(string) $scrape['body_bytes']);
                        }
                        if (isset($scrape['signals']) && is_array($scrape['signals'])) {
                            $this->line('scrape.signals: '.json_encode($scrape['signals'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        }
                    }

                    if (isset($result['debug']['scrape_attempts']) && is_array($result['debug']['scrape_attempts'])) {
                        $this->line('scrape.attempts: '.json_encode($result['debug']['scrape_attempts'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }

                    if (isset($result['debug']['extracted']) && is_array($result['debug']['extracted'])) {
                        $ex = $result['debug']['extracted'];
                        $this->line('extracted.title: '.(string) ($ex['title'] ?? ''));
                        $this->line('extracted.sku: '.(string) ($ex['sku'] ?? ''));
                        $this->line('extracted.meta_description: '.(string) ($ex['description'] ?? ''));
                        $this->line('extracted.product_description: '.(string) ($ex['product_description'] ?? ''));
                        $this->line('extracted.other_text: '.(string) ($ex['text'] ?? ''));
                    }

                    if (isset($result['debug']['openai']) && is_array($result['debug']['openai'])) {
                        $openai = $result['debug']['openai'];
                        if (($openai['enabled'] ?? null) !== null) {
                            $this->line('openai.enabled: '.json_encode($openai['enabled']));
                        }
                        if (($openai['reason'] ?? '') !== '') {
                            $this->line('openai.reason: '.(string) $openai['reason']);
                        }
                        if (array_key_exists('driver', $openai)) {
                            $this->line('openai.driver: '.json_encode($openai['driver']));
                        }
                        if (($openai['error'] ?? '') !== '') {
                            $this->line('openai.error: '.(string) $openai['error']);
                        }
                        if (($openai['endpoint'] ?? '') !== '') {
                            $this->line('openai.endpoint: '.(string) $openai['endpoint']);
                        }
                        if (($openai['model'] ?? '') !== '') {
                            $this->line('openai.model: '.(string) $openai['model']);
                        }
                        if (($openai['http_status'] ?? null) !== null) {
                            $this->line('openai.http_status: '.(string) $openai['http_status']);
                        }
                        if (($openai['response_body'] ?? '') !== '') {
                            $this->newLine();
                            $this->line('openai.response_body:');
                            $this->line((string) $openai['response_body']);
                        }
                        if (($openai['prompt'] ?? '') !== '') {
                            $this->newLine();
                            $this->line('openai.prompt:');
                            $this->line((string) $openai['prompt']);
                        }
                        if (($openai['content'] ?? '') !== '') {
                            $this->newLine();
                            $this->line('openai.content:');
                            $this->line((string) $openai['content']);
                        }
                        if (isset($openai['decoded']) && is_array($openai['decoded'])) {
                            $this->line('openai.decoded: '.json_encode($openai['decoded'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        }
                    }

                    if (isset($result['debug']['openai_secondary']) && is_array($result['debug']['openai_secondary'])) {
                        $openai2 = $result['debug']['openai_secondary'];
                        if (($openai2['enabled'] ?? null) !== null) {
                            $this->line('openai_secondary.enabled: '.json_encode($openai2['enabled']));
                        }
                        if (($openai2['reason'] ?? '') !== '') {
                            $this->line('openai_secondary.reason: '.(string) $openai2['reason']);
                        }
                        if (array_key_exists('driver', $openai2)) {
                            $this->line('openai_secondary.driver: '.json_encode($openai2['driver']));
                        }
                        if (($openai2['model'] ?? '') !== '') {
                            $this->line('openai_secondary.model: '.(string) $openai2['model']);
                        }
                        if (($openai2['http_status'] ?? null) !== null) {
                            $this->line('openai_secondary.http_status: '.(string) $openai2['http_status']);
                        }
                        if (($openai2['response_body'] ?? '') !== '') {
                            $this->newLine();
                            $this->line('openai_secondary.response_body:');
                            $this->line((string) $openai2['response_body']);
                        }
                        if (($openai2['prompt'] ?? '') !== '') {
                            $this->newLine();
                            $this->line('openai_secondary.prompt:');
                            $this->line((string) $openai2['prompt']);
                        }
                        if (($openai2['content'] ?? '') !== '') {
                            $this->newLine();
                            $this->line('openai_secondary.content:');
                            $this->line((string) $openai2['content']);
                        }
                        if (isset($openai2['decoded']) && is_array($openai2['decoded'])) {
                            $this->line('openai_secondary.decoded: '.json_encode($openai2['decoded'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        }
                        if (($openai2['error'] ?? '') !== '') {
                            $this->line('openai_secondary.error: '.(string) $openai2['error']);
                        }
                    }

                    if (isset($result['debug']['decision']) && is_array($result['debug']['decision'])) {
                        $this->line('decision: '.json_encode($result['debug']['decision'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }

                    if (($result['debug']['html_snippet'] ?? '') !== '') {
                        $this->newLine();
                        $this->line('html_snippet:');
                        $this->line((string) $result['debug']['html_snippet']);
                    }

                    $this->line('--- /DEBUG ---');
                    $this->newLine();
                }

                $this->info(sprintf(
                    'OK : repairs=%d products=%d | piece="%s"',
                    $result['repairs_updated'],
                    $result['products_updated'],
                    $result['piece_label']
                ));

                $ok++;
            } catch (\Throwable $e) {
                $this->error("FAIL : {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Terminé. OK={$ok} FAIL={$failed} (dry-run=" . ($dryRun ? 'yes' : 'no') . ').');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function flushQueueTables(): void
    {
        $driver = config('queue.default');
        if ($driver !== 'database') {
            $this->warn("Flush queue ignoré (driver={$driver}, uniquement database).");
            return;
        }

        if (Schema::hasTable('jobs')) {
            DB::table('jobs')->truncate();
            $this->info('Table jobs tronquée.');
        }
        if (Schema::hasTable('failed_jobs')) {
            DB::table('failed_jobs')->truncate();
            $this->info('Table failed_jobs tronquée.');
        }
    }
}
