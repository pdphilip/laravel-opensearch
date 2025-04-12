<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use PDPhilip\Elasticsearch\Utils\Helpers;
use PDPhilip\OpenSearch\Connection;
use PDPhilip\OpenSearch\OpenClient as Client;
use PDPhilip\OpenSearch\Schema\Builder as SchemaBuilder;

function getLaravelVersion(): int
{
    try {
        return Helpers::getLaravelCompatabilityVersion();
    } catch (Exception $e) {
        return 0;
    }
}

test('Laravel Compatability for v'.getLaravelVersion().' loaded', function () {
    expect(getLaravelVersion())->toBeGreaterThan(10)
        ->and(getLaravelVersion())->toBeLessThan(13);
});

test('Connection', function () {
    $connection = DB::connection('opensearch');

    expect($connection)->toBeInstanceOf(Connection::class)
        ->and($connection->getDriverName())->toEqual('opensearch')
        ->and($connection->getDriverTitle())->toEqual('opensearch');
});

test('Reconnect', function () {
    $c1 = DB::connection('opensearch');
    $c2 = DB::connection('opensearch');
    expect(spl_object_hash($c1) === spl_object_hash($c2))->toBeTrue();

    $c1 = DB::connection('opensearch');
    DB::purge('opensearch');
    $c2 = DB::connection('opensearch');
    expect(spl_object_hash($c1) !== spl_object_hash($c2))->toBeTrue();
});

test('Disconnect And Create New Connection', function () {
    $connection = DB::connection('opensearch');
    expect($connection)->toBeInstanceOf(Connection::class);
    $client = $connection->getClient();
    expect($client)->toBeInstanceOf(Client::class);

    $connection->disconnect();
    $client = $connection->getClient();
    expect($client)->not()->toBeNull();
    DB::purge('opensearch');

    $connection = DB::connection('opensearch');
    expect($connection)->toBeInstanceOf(Connection::class);
    $client = $connection->getClient();
    expect($client)->toBeInstanceOf(Client::class);

});

test('DB', function () {
    $connection = DB::connection('opensearch');
    expect($connection->getClient())->toBeInstanceOf(Client::class);
});

test('Prefix', function () {
    $config = [
        'name' => 'test',
        'auth_type' => 'http',
        'hosts' => ['http://localhost:9200'],
        'index_prefix' => 'prefix_',
    ];

    $connection = new Connection($config);

    expect($connection->getIndexPrefix())->toBe('prefix_');
});

test('Schema Builder', function () {
    $schema = DB::connection('opensearch')->getSchemaBuilder();
    expect($schema)->toBeInstanceOf(SchemaBuilder::class);
});

test('Driver Name', function () {
    $driver = DB::connection('opensearch')->getDriverName();
    expect($driver === 'opensearch')->toBeTrue();
});
