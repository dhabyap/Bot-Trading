<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ============================================================
        // Bot Trading: Cek pasar & eksekusi sinyal setiap 1 menit
        // Sesuaikan interval sesuai kebutuhan strategi:
        //   ->everyMinute()     = setiap 1 menit
        //   ->everyFiveMinutes() = setiap 5 menit
        //   ->hourly()          = setiap jam
        // ============================================================
        $schedule->command('trading:run')
                 ->everyMinute()
                 ->withoutOverlapping()   // Cegah double-run jika proses sebelumnya belum selesai
                 ->runInBackground()      // Jalankan di background (non-blocking)
                 ->appendOutputTo(storage_path('logs/trading-bot.log')); // Log output ke file
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
