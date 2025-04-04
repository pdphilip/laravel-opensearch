<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\DynamicIndex;
use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class PageHit extends Model
{
    use DynamicIndex;

    protected $connection = 'elasticsearch';

    protected $table = 'page_hits';

    protected static $unguarded = true;

    public static function executeSchema()
    {
        $schema = Schema::connection('elasticsearch');

        collect([
            '2021-01-01',
            '2021-01-02',
            '2021-01-03',
            '2021-01-04',
            '2021-01-05',
        ])->each(function (string $index) use ($schema) {
            Schema::dropIfExists('page_hits');

            Schema::dropIfExists('page_hits_'.$index);

            $schema->create('page_hits_'.$index, function (Blueprint $table) {
                $table->date('created_at');
                $table->date('updated_at');
            });

        });

    }
}
