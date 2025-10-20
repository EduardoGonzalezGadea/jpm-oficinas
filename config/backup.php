<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Destination
    |--------------------------------------------------------------------------
    |
    | Define los discos de Laravel donde se almacenarán las copias de seguridad.
    | Por defecto usamos el disco 'local' (storage/app/backup).
    |
    */

    'backup' => [
        'name' => env('APP_NAME', 'laravel'),

        'source' => [
            'files' => [
                'include' => [
                    base_path(),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    storage_path(),
                ],
                'follow_links' => false,
            ],

            'databases' => [
                'mysql',
            ],
        ],

        'destination' => [
            'disks' => [
                'local',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ruta del binario de mysqldump (opcional)
    |--------------------------------------------------------------------------
    |
    | Si tu entorno Windows no tiene `mysqldump` en el PATH, puedes indicar la
    | carpeta que contiene `mysqldump.exe` aquí o en la variable de entorno
    | `DB_DUMP_BINARY_PATH`.
    |
    */
    'db_dump_binary_path' => env('DB_DUMP_BINARY_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Cleanup strategy
    |--------------------------------------------------------------------------
    |
    | Mantener los backups por 30 días. Usamos la estrategia por defecto de
    | Spatie y configuramos que retenga backups diarios durante 30 días.
    |
    */
    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 30,
            'keep_daily_backups_for_days' => 30,
            'keep_weekly_backups_for_weeks' => 0,
            'keep_monthly_backups_for_months' => 0,
            'keep_yearly_backups_for_years' => 0,
            'delete_oldest_backups_when_using_more_megabytes_than' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications (disabled by default)
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => null,

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => null,
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => null,
            'username' => '',
            'avatar_url' => '',
        ],

        'null' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Archives settings
    |--------------------------------------------------------------------------
    */
    'archive' => [
        'disks' => [
            'local',
        ],
        'time_before_remove' => 10,
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'laravel'),
            'disks' => ['local'],
            'newest_backup_should_not_be_older_than_days' => 1,
            'storage_used_in_megabytes_should_be_less_than' => 5000,
        ],
    ],

    'cleanup_defaults' => [],
];
