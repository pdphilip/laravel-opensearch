<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PDPhilip\OpenSearch\OpenSearchServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            OpenSearchServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'opensearch');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);

        $app['config']->set('database.connections.opensearch', [
            'driver' => 'opensearch',
            'hosts' => ['http://localhost:9200'],
            'options' => [
                'logging' => true,
            ],
        ]);

        $app['config']->set('database.connections.opensearch_unsafe', [
            'driver' => 'opensearch',
            'hosts' => ['http://localhost:9200'],
            'options' => [
                'bypass_map_validation' => true,
                'insert_chunk_size' => 10000,
                'logging' => true,
            ],
        ]);
    }
}
