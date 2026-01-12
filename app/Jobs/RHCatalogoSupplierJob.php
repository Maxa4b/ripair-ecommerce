<?php

namespace App\Jobs;

use App\Models\Catalog\Product;
use App\Services\RHCatalogo\Exceptions\LlmUnavailableException;
use App\Services\RHCatalogo\RHCatalogoService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RHCatalogoSupplierJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $supplierRef;

    public function __construct(string $supplierRef)
    {
        $this->supplierRef = $supplierRef;
    }

    public function handle(RHCatalogoService $service): void
    {
        $lockKey = "rhcatalogo:supplier_ref:{$this->supplierRef}";

        Cache::lock($lockKey, 300)->block(10, function () use ($service) {
            $now = CarbonImmutable::now();

            Product::query()
                ->where('supplier_reference', $this->supplierRef)
                ->update([
                    'rhc_status' => 'processing',
                    'rhc_last_run_at' => $now,
                    'rhc_last_error' => null,
                    'rhc_last_payload' => json_encode([
                        'stage' => 'processing',
                        'supplier_ref' => $this->supplierRef,
                        'at' => $now->toISOString(),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);

            try {
                $service->processSupplierRef($this->supplierRef);
            } catch (LlmUnavailableException $e) {
                $delay = (int) config('rhcatalogo.queue_retry_delay', 60);
                $delay = max(5, $delay);

                Log::info('[RHCatalogo] LLM unavailable, pausing job', [
                    'supplier_ref' => $this->supplierRef,
                    'delay_s' => $delay,
                    'error' => $e->getMessage(),
                ]);

                Product::query()
                    ->where('supplier_reference', $this->supplierRef)
                    ->update([
                        'rhc_status' => 'paused',
                        'rhc_last_error' => null,
                        'rhc_last_run_at' => $now,
                        'rhc_last_payload' => json_encode([
                            'stage' => 'paused',
                            'supplier_ref' => $this->supplierRef,
                            'reason' => 'llm_unavailable',
                            'endpoint' => $e->endpoint,
                            'delay_s' => $delay,
                            'error' => $e->getMessage(),
                            'at' => $now->toISOString(),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);

                $this->release($delay);
                return;
            } catch (Throwable $e) {
                Log::warning('[RHCatalogo] supplier_ref processing failed', [
                    'supplier_ref' => $this->supplierRef,
                    'error' => $e->getMessage(),
                ]);

                Product::query()
                    ->where('supplier_reference', $this->supplierRef)
                    ->update([
                        'rhc_status' => 'error',
                        'rhc_last_error' => $e->getMessage(),
                        'rhc_last_run_at' => $now,
                        'rhc_last_payload' => json_encode([
                            'stage' => 'error',
                            'supplier_ref' => $this->supplierRef,
                            'error' => $e->getMessage(),
                            'at' => $now->toISOString(),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);

                throw $e;
            }
        });
    }
}
