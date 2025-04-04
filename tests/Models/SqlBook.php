<?php

declare(strict_types=1);

namespace PDPhilip\Elasticsearch\Tests\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Support\Facades\Schema;
use PDPhilip\Elasticsearch\Eloquent\HybridRelations;

use function assert;

class SqlBook extends EloquentModel
{
    use HybridRelations;

    protected $connection = 'sqlite';

    protected $table = 'books';

    protected static $unguarded = true;

    protected $primaryKey = 'title';

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Check if we need to run the schema.
     */
    public static function executeSchema(): void
    {
        $schema = Schema::connection('sqlite');
        assert($schema instanceof SQLiteBuilder);

        $schema->dropIfExists('books');
        $schema->create('books', function (Blueprint $table) {
            $table->string('title');
            $table->string('author_id')->nullable();
            $table->integer('sql_user_id')->unsigned()->nullable();
            $table->timestamps();
        });
    }
}
