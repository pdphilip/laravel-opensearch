<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Birthday extends Model
{
    protected $connection = 'elasticsearch';

    protected $table = 'birthday';

    protected $fillable = ['name', 'birthday'];

    protected $casts = ['birthday' => 'datetime'];

    /**
     * Check if we need to run the schema.
     */
    public static function executeSchema()
    {
        $schema = Schema::connection('elasticsearch');

        $schema->dropIfExists('birthday');
        $schema->create('birthday', function (Blueprint $table) {
            $table->date('birthday');
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
