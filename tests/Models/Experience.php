<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Experience extends Model
{
    protected $connection = 'elasticsearch';

    protected $table = 'experiences';

    protected static $unguarded = true;

    protected $casts = ['years' => 'int'];

    public function sqlUsers()
    {
        return $this->morphToMany(SqlUser::class, 'experienced');
    }

    public static function executeSchema()
    {
        $schema = Schema::connection('elasticsearch');

        $schema->dropIfExists('experienceds');
        $schema->create('experienceds', function (Blueprint $table) {
            $table->date('created_at');
            $table->date('updated_at');
        });

        $schema->dropIfExists('experiences');
        $schema->create('experiences', function (Blueprint $table) {
            $table->string('name');
            $table->keyword('sql_user_id');
            $table->string('author');
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
