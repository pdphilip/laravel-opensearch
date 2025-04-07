<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

/**
 * @property string $title
 * @property string $author
 * @property array $chapters
 */
class Skill extends Model
{
    protected $connection = 'opensearch';

    protected $index = 'skills';

    protected static $unguarded = true;

    public function sqlUsers(): BelongsToMany
    {
        return $this->belongsToMany(SqlUser::class);
    }

    public static function executeSchema()
    {
        $schema = Schema::connection('opensearch');

        $schema->dropIfExists('skill_sql_user');
        $schema->create('skill_sql_user', function (Blueprint $table) {
            $table->string('skill_ids');
            $table->string('sql_user_ids');
            $table->date('created_at');
            $table->date('updated_at');
        });

        $schema->dropIfExists('skills');
        $schema->create('skills', function (Blueprint $table) {
            $table->string('name');
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
