<?php

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Schema\Blueprint;
use PDPhilip\OpenSearch\Schema\Schema;

/**
 * @property string $name
 * @property string $country
 * @property bool $can_be_eaten
 */
class HiddenAnimal extends Model
{
    protected $connection = 'opensearch';

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'country',
        'can_be_eaten',
    ];

    protected $hidden = ['country'];

    public static function executeSchema()
    {
        $schema = Schema::connection('opensearch');

        $schema->dropIfExists('hidden_animals');
        $schema->create('hidden_animals', function (Blueprint $table) {

            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
