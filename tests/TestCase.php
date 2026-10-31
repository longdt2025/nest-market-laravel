<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    public function seed($class = 'Database\\Seeders\\DatabaseSeeder')
    {
        parent::seed($class);
        // Customer fixtures belong to isolated tests, not the production/demo seeder.
        if ($class === DatabaseSeeder::class) {
            User::firstOrCreate(['email' => 'customer@nest.test'], ['name' => 'Khách hàng Demo', 'password' => 'Password@123', 'role' => 'customer']);
        }

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio_media');
    }

    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:'
            || $app['config']->get('database.connections.sqlite.url')) {
            throw new \RuntimeException('Tests must use isolated SQLite :memory:, never the Docker/demo database.');
        }

        return $app;
    }
}
