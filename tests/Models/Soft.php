<?php

namespace PDPhilip\OpenSearch\Tests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Eloquent\SoftDeletes;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Soft extends Model
{
    use MassPrunable;
    use SoftDeletes;

    protected $connection = 'opensearch';

    protected static $unguarded = true;

    protected $casts = ['deleted_at' => 'datetime'];

    public function prunable(): Builder
    {
        return $this->newQuery();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if we need to run the schema.
     */
    public static function executeSchema()
    {
        $schema = Schema::connection('opensearch');

        $schema->dropIfExists('softs');
        $schema->create('softs', function (Blueprint $table) {
            $table->date('deleted_at');
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
