<?php
namespace Tests;
abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
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
