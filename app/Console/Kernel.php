<?php

namespace App\Console;

use App\Models\NonceCache;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            NonceCache::where('expires_at', '<', now())->delete();
        })->daily()->name('flush-expired-nonces');

        // Sinkronisasi otomatis transaksi Xendit setiap jam (atau saat scheduler berjalan)
        $schedule->command('xendit:sync-transactions --days=7 --limit=100')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
