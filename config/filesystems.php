<?php

return [
    'default' => env('FILESYSTEM_DISK', 'public'),
    'disks' => [
        'minio' => [
            'driver' => 's3', 'key' => env('MINIO_ACCESS_KEY', env('MINIO_ROOT_USER')),
            'secret' => env('MINIO_SECRET_KEY', env('MINIO_ROOT_PASSWORD')),
            'region' => 'us-east-1', 'bucket' => env('MINIO_BUCKET', 'nest-market-backups'),
            'endpoint' => env('MINIO_ENDPOINT', 'http://127.0.0.1:19000'),
            'use_path_style_endpoint' => true, 'throw' => true,
            'http' => ['connect_timeout' => 3, 'timeout' => 15],
        ],
        'local' => ['driver' => 'local', 'root' => storage_path('app/private'), 'serve' => true, 'throw' => false],
        'public' => ['driver' => 'local', 'root' => storage_path('app/public'), 'url' => env('APP_URL').'/storage', 'visibility' => 'public', 'throw' => false],
    ],
    'links' => [public_path('storage') => storage_path('app/public')],
];
