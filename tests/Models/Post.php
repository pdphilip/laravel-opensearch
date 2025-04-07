<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Post extends Model
{
    protected $connection = 'opensearch';

    protected $table = 'post';

    protected static $unguarded = true;

    protected $queryFieldMap = [
        'comments.country' => 'comments.country.keyword',
        'comments.likes' => 'comments.likes',
    ];

    public static function executeSchema()
    {
        $schema = Schema::connection('opensearch');

        Schema::dropIfExists('post');

        $schema->create('post', function (Blueprint $table) {
            $table->text('title', hasKeyword: true);
            $table->integer('status');
            $table->nested('comments');

            $table->date('created_at');
            $table->date('updated_at');
        });

    }
}
