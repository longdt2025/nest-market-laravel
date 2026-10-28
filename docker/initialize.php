<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! config('app.key')) {
    $status = Artisan::call('key:generate', ['--force' => true]);
    echo Artisan::output();
    if ($status !== 0) {
        exit($status);
    }
}

Artisan::call('config:clear');
echo Artisan::output();

// The current seeder overwrites stock/passwords; only seed a new database.
$newDatabase = ! Schema::hasTable('users')
    && ! Schema::hasTable('products')
    && ! Schema::hasTable('orders');

$status = Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();
if ($status !== 0) {
    exit($status);
}

if ($newDatabase) {
    $status = Artisan::call('db:seed', ['--force' => true]);
    echo Artisan::output();
    if ($status !== 0) {
        exit($status);
    }
} else {
    echo "Existing database preserved; demo seeding skipped.\n";
}

echo "NEST Market Docker setup completed.\n";
