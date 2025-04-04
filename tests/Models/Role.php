<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

/**
 * @property string $title
 * @property string $author
 * @property array $chapters
 */
class Role extends Model
{
    protected $connection = 'elasticsearch';

    protected $index = 'roles';

    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sqlUser()
    {
        return $this->belongsTo(SqlUser::class);
    }

    /**
     * Check if we need to run the schema.
     */
    public static function executeSchema()
    {
        $schema = Schema::connection('elasticsearch');

        $schema->dropIfExists('roles');
        $schema->create('roles', function (Blueprint $table) {
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
