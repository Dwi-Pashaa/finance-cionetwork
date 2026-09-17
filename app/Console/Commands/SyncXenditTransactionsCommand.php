<?php

namespace App\Console\Commands;

use App\Services\Xendit\XenditService;
use Illuminate\Console\Command;

class SyncXenditTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xendit:sync-transactions
                            {--days=30 : Jumlah hari transaksi ke belakang yang akan disinkronisasi}
                            {--limit=100 : Jumlah maksimum transaksi yang ditarik dari Xendit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi riwayat transaksi Xendit Gateway ke database Activity Log & Keuangan';

    /**
     * Execute the console command.
     */
    public function handle(XenditService $xenditService): int
    {
        $this->info('Memulai sinkronisasi transaksi dari Xendit Gateway...');

        if (! $xenditService->isConfigured()) {
            $this->error('Xendit Secret Key belum dikonfigurasi di file .env.');

            return self::FAILURE;
        }

        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');

        $this->line("• Rentang Waktu : {$days} hari terakhir");
        $this->line("• Batas Maksimal: {$limit} transaksi");

        $result = $xenditService->syncTransactionsToDatabase($limit, $days);

        $this->newLine();
        $this->info("✓ Sinkronisasi Selesai:");
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Diambil dari Xendit', $result['total_fetched']],
                ['Berhasil Disimpan ke Log API', $result['synced_count']],
                ['Dilewati (Sudah Ada Sebelumnya)', $result['skipped_count']],
            ]
        );

        return self::SUCCESS;
    }
}
