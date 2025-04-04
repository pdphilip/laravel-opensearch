<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use Carbon\Carbon;
use PDPhilip\OpenSearch\Eloquent\Builder;
use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Relations\BelongsTo;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

/** @property Carbon $created_at */
class Item extends Model
{
    protected $connection = 'elasticsearch';

    protected $index = 'items';

    protected static $unguarded = true;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSharp(Builder $query)
    {
        return $query->where('type', 'sharp');
    }

    public static function executeSchema()
    {
        $schema = Schema::connection('elasticsearch');

        $schema->dropIfExists('items');
        $schema->create('items', function (Blueprint $table) {

            $table->text('name', hasKeyword: true);
            $table->keyword('user_id');

            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
