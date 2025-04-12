<?php

namespace PDPhilip\OpenSearch\Laravel\v11\Schema;

use PDPhilip\OpenSearch\Schema\Blueprint;

trait GrammarCompatibility
{
    private function createBlueprint(Blueprint $blueprint): Blueprint
    {
        // @phpstan-ignore-next-line
        return new Blueprint('');
    }
}
