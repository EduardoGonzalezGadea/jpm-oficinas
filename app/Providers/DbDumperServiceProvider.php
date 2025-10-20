<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DbDumperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Escuchar el evento DumpingDatabase y aplicar la ruta del binario si corresponde
        \Event::listen(\Spatie\Backup\Events\DumpingDatabase::class, function ($event) {
            $dbDumper = $event->dbDumper;
            try {
                \Log::info('[DbDumperServiceProvider] DumpingDatabase event fired for dumper: ' . get_class($dbDumper));
            } catch (\Throwable $e) {
                // ignore logging failures
            }

            // Solo para MySql dumper
            if (! ($dbDumper instanceof \Spatie\DbDumper\Databases\MySql)) {
                try {
                    \Log::info('[DbDumperServiceProvider] Dumper is not MySql, skipping. Class: ' . get_class($dbDumper));
                } catch (\Throwable $e) {
                }
                return;
            }

            // Leer primero desde config, luego desde env
            $path = config('backup.db_dump_binary_path') ?? env('DB_DUMP_BINARY_PATH', '');

            if (empty($path)) {
                $possible = [
                    'C:\\xampp\\mysql\\bin',
                    'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin',
                    'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin',
                    'C:\\Program Files (x86)\\MySQL\\MySQL Server 5.7\\bin',
                ];

                foreach ($possible as $p) {
                    \Log::info('[DbDumperServiceProvider] Checking possible path: ' . $p);
                    if (file_exists($p) && is_dir($p) && file_exists($p . DIRECTORY_SEPARATOR . 'mysqldump.exe')) {
                        $path = $p;
                        \Log::info('[DbDumperServiceProvider] Found mysqldump at: ' . $path);
                        break;
                    }
                }
            }

            if (! empty($path)) {
                \Log::info('[DbDumperServiceProvider] Applying dump binary path: ' . $path);
                $dbDumper->setDumpBinaryPath($path);
            }
        });
    }
}
