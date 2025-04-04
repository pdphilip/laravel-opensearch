<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

class Photo extends Model
{
    protected $connection = 'elasticsearch';

    protected $table = 'photos';

    protected static $unguarded = true;

    public function hasImage(): MorphTo
    {
        return $this->morphTo();
    }

    public function hasImageWithCustomOwnerKey(): MorphTo
    {
        return $this->morphTo(ownerKey: 'cclient_id');
    }

    public static function executeSchema()
    {
        $schema = Schema::connection('elasticsearch');

        $schema->dropIfExists('photos');
        $schema->create('photos', function (Blueprint $table) {
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
