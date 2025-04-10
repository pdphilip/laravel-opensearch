<?php

use Illuminate\Support\Facades\DB;
use PDPhilip\OpenSearch\Exceptions\QueryException;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;
use PDPhilip\OpenSearch\Tests\Factories\ProductFactory;
use PDPhilip\OpenSearch\Tests\Models\Product;

it('re-indexs data', function () {
    // Drop the Schema
    Schema::deleteIfExists('products');
    Schema::deleteIfExists('holding_products');

    // Create the Schema For this data set before each test
    Schema::connection('opensearch')->create('products', function (Blueprint $index) {
        $index->text('name');
        $index->float('price');
        $index->integer('status');
        $index->date('created_at');
        $index->date('updated_at');
    });

    Schema::connection('opensearch')->create('holding_products', function (Blueprint $index) {
        $index->text('name');
        $index->float('price');
        $index->integer('status');
        $index->geo('manufacturer.location');
        $index->date('created_at');
        $index->date('updated_at');
    });

    $productsSchema = Schema::connection('opensearch')->getIndex('products');
    $productsHoldingSchema = Schema::connection('opensearch')->getIndex('holding_products');

    expect(! empty($productsSchema['products']['mappings']))->toBeTrue()
        ->and(! empty($productsSchema['products']['settings']))->toBeTrue()
        ->and(! empty($productsHoldingSchema['holding_products']['mappings']))->toBeTrue()
        ->and(! empty($productsHoldingSchema['holding_products']['mappings']['properties']['manufacturer']['properties']['location']['type'] == 'geo_point'))->toBeTrue()
        ->and(! empty($productsHoldingSchema['holding_products']['settings']))->toBeTrue();

    $pf = new ProductFactory;
    $products = [];
    $i = 0;
    while ($i < 100) {
        $products[] = $pf->definition();
        $i++;
    }
    DB::connection('opensearch')->table('products')->insert($products);

    $find = DB::connection('opensearch')->table('products')->get();

    expect(count($find))->toEqual(100);

    try {
        Product::whereGeoDistance('manufacturer.location', '10000km', [0, 0])->get();
    } catch (QueryException $exception) {
        expect($exception->getMessage())->toContain('failed to find geo field')
            ->and($exception->getDetails())->toBeArray('failed to find geo field');
    }

    $reindex = Schema::reIndex('products', 'holding_products');
    sleep(2);
    expect($reindex['created'])->toEqual(100);

    $findOld = DB::connection('opensearch')->table('products')->count();
    $findNew = DB::connection('opensearch')->table('holding_products')->count();
    // Sleep to allow ES to catch up

    expect($findOld)->toEqual(100)
        ->and($findNew)->toEqual(100);

    Schema::connection('opensearch')->deleteIfExists('products');
    expect(Schema::connection('opensearch')->indexExists('products'))->toBeFalse();

    // Now let's create the products index again but with proper mapping
    Schema::connection('opensearch')->create('products', function (Blueprint $index) {
        $index->text('name');
        $index->float('price');
        $index->integer('status');
        $index->geo('manufacturer.location');
        $index->date('created_at');
        $index->date('updated_at');
    });
    $product = Schema::connection('opensearch')->getIndex('products');
    expect(! empty($product['products']['mappings']))->toBeTrue()
        ->and(! empty($product['products']['settings']))->toBeTrue();

    // now we move new to old.
    $reindex = Schema::reIndex('holding_products', 'products');
    $this->assertTrue($reindex['created'] == 100);
    // Sleep to allow ES to catch up
    sleep(2);

    $countOriginal = DB::connection('opensearch')->table('products')->count();
    $countHolding = DB::connection('opensearch')->table('holding_products')->count();

    expect($countOriginal)->toEqual(100)
        ->and($countHolding)->toEqual(100);

    $found = Product::whereGeoDistance('manufacturer.location', '10000km', [0, 0])->get();
    expect($found->isNotEmpty())->toBeTrue();

    // Cleanup
    Schema::deleteIfExists('products');
    Schema::deleteIfExists('holding_products');

    expect(Schema::indexExists('products'))->toBeFalse()
        ->and(Schema::indexExists('holding_products'))->toBeFalse();

});
