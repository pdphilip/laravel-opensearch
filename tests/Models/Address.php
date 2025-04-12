<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Address extends Model
{
    protected $connection = 'opensearch';

    protected $table = 'address';

    protected static $unguarded = true;

    public static function executeSchema()
    {
        $schema = Schema::connection('opensearch');

        $schema->dropIfExists('address');
        $schema->createIfNotExists('address', function (Blueprint $table) {
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
