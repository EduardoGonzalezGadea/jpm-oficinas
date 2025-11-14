<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Limpieza de backups (elimina backups antiguos) a la 01:00 todos los días
        $schedule->command('backup:clean')
                 ->dailyAt('01:00')
                 ->withoutOverlapping()
                 ->onOneServer();

        // Crear backup completo a las 03:00 todos los días
        $schedule->command('backup:run')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
