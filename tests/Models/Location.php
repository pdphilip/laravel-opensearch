<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Location extends Model
{
    protected $keyType = 'string';

    protected $connection = 'elasticsearch';

    protected $table = 'locations';

    protected static $unguarded = true;

    public static function executeSchema()
    {
        $schema = Schema::connection('elasticsearch');

        $schema->dropIfExists('locations');
        $schema->create('locations', function (Blueprint $table) {
            $table->geoPoint('point');
            $table->geoShape('location');

            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
