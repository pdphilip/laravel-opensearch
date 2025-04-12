<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Book extends Model
{
    protected $connection = 'opensearch';

    protected $index = 'books';

    protected static $unguarded = true;

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function sqlAuthor(): BelongsTo
    {
        return $this->belongsTo(SqlUser::class, 'author_id');
    }

    public static function executeSchema()
    {
        $schema = Schema::connection('opensearch');

        $schema->dropIfExists('books');
        $schema->create('books', function (Blueprint $table) {
            $table->keyword('author_id');
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
