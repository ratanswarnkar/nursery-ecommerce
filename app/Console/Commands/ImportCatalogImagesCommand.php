<?php

namespace App\Console\Commands;

use App\Services\Catalog\BulkProductImageImporter;
use Illuminate\Console\Command;

class ImportCatalogImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:import-images {--dir= : Custom directory to scan for product images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and link local product catalog images deterministically based on product slug';

    /**
     * Execute the console command.
     */
    public function handle(BulkProductImageImporter $importer): int
    {
        $dir = $this->option('dir');
        $this->info('Scanning for local product images...');

        $stats = $importer->importFromDirectory($dir);

        $this->info("Matched and linked: {$stats['matched']} images ({$stats['created']} created, {$stats['updated']} updated, {$stats['skipped']} skipped).");

        return self::SUCCESS;
    }
}
