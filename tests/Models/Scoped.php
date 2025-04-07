<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Builder;
use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Scoped extends Model
{
    protected $connection = 'opensearch';

    protected $fillable = ['name', 'favorite'];

    protected $table = 'scoped';

    protected $casts = ['birthday' => 'datetime'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('favorite', function (Builder $builder) {
            $builder->where('favorite', true);
        });
    }

    /**
     * Check if we need to run the schema.
     */
    public static function executeSchema()
    {
        $schema = Schema::connection('opensearch');

        $schema->dropIfExists('scoped');
        $schema->create('scoped', function (Blueprint $table) {
            $table->date('birthday');
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
