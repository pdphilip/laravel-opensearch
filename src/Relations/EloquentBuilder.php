<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Relations;

use Illuminate\Database\Eloquent\Builder;
use PDPhilip\OpenSearch\Relations\Traits\QueriesRelationships;

class EloquentBuilder extends Builder
{
    use QueriesRelationships;
}
