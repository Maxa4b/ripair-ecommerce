<?php

namespace App\Services\Suppliers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobileSentrixStockService
{
    private const OUT_OF_STOCK_MARKERS = [
        'productoutofstockform',
        'outofstockbtn_',
        '>notify me<',
    ];

    public function checkAvailability(string $url): ?bool
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (!Str::contains(Str::lower($url), 'mobilesentrix')) {
            Log::info('[StockSync] URL fournisseur ignoree (non MobileSentrix).', ['url' => $url]);
            return null;
        }

        try {
            $resp = Http::retry(2, 250)
                ->timeout(20)
                ->withOptions(['allow_redirects' => true])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9,fr-FR;q=0.8,fr;q=0.7',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('[StockSync] HTTP exception.', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }

        if (!$resp->ok()) {
            Log::warning('[StockSync] HTTP status non OK.', ['url' => $url, 'status' => $resp->status()]);
            return null;
        }

        $html = $resp->body();
        if (strlen($html) < 500) {
            Log::warning('[StockSync] HTML trop court, skip.', ['url' => $url]);
            return null;
        }

        $lower = Str::lower($html);
        $outOfStock = Str::contains($lower, self::OUT_OF_STOCK_MARKERS);

        return !$outOfStock;
    }
}
