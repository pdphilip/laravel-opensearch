<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use PDPhilip\OpenSearch\Eloquent\Model;

class Guarded extends Model
{
    protected $connection = 'elasticsearch';

    protected $table = 'guarded';

    protected $guarded = ['foobar', 'level1->level2'];
}
