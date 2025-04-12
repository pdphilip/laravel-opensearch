<?php

namespace PDPhilip\OpenSearch\Laravel\v12\Schema;

use PDPhilip\OpenSearch\Schema\Blueprint;

trait GrammarCompatibility
{
    private function createBlueprint(Blueprint $blueprint): Blueprint
    {
        return new Blueprint($blueprint->getConnection(), '');
    }
}
