<?php

namespace App\Console\Commands;

use App\Services\Suppliers\MobileSentrixStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SupplierStockSync extends Command
{
    protected $signature = 'supplier:stock-sync
        {--limit=0 : Nombre max d\'URLs a traiter (0 = toutes)}
        {--dry-run : N\'ecrit rien en base}';

    protected $description = 'Met a jour repairs.supplier_stock via MobileSentrix (Notify Me = rupture)';

    public function handle(MobileSentrixStockService $service): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $query = DB::table('repairs')
            ->select('supplier_url')
            ->whereNotNull('supplier_url')
            ->where('supplier_url', '!=', '')
            ->distinct();

        if ($limit > 0) {
            $query->limit($limit);
        }

        $urls = $query->pluck('supplier_url');

        if ($urls->isEmpty()) {
            $this->warn('Aucune URL fournisseur trouvee.');
            return self::SUCCESS;
        }

        $rowsUpdated = 0;
        $failed = 0;

        foreach ($urls as $url) {
            $availability = $service->checkAvailability((string) $url);
            if ($availability === null) {
                $failed++;
                continue;
            }

            $stockValue = $availability ? 1 : 0;
            if ($dryRun) {
                $this->line("[DRY] {$url} => {$stockValue}");
                continue;
            }

            $rowsUpdated += DB::table('repairs')
                ->where('supplier_url', $url)
                ->update(['supplier_stock' => $stockValue]);
        }

        $this->info("Termine. URLs: {$urls->count()}, lignes maj: {$rowsUpdated}, echecs: {$failed}.");

        return self::SUCCESS;
    }
}
