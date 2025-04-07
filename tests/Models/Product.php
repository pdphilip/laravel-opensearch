<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Product extends Model
{
    protected $connection = 'opensearch';

    protected $table = 'products';

    protected static $unguarded = true;

    /**
     * Check if we need to run the schema.
     */
    public static function executeSchema()
    {
        $schema = Schema::connection('opensearch');

        $schema->dropIfExists('products');
        $schema->create('products', function (Blueprint $table) {
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
