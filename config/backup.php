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
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
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
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['null'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['null'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['null'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['null'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['null'],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => ['null'],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => 'backup@example.com',

            'from' => [
                'address' => 'noreply@example.com',
                'name' => 'Oficinas Backup',
            ],
        ],

        'slack' => [
            'webhook_url' => 'https://hooks.slack.com/services/example',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => 'https://discord.com/api/webhooks/example',
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
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup_defaults' => [],
];
