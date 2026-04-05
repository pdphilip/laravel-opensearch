<?php

namespace PDPhilip\OpenSearch\Laravel\Compatibility\Schema;

use PDPhilip\Elasticsearch\Utils\Helpers;
use PDPhilip\OpenSearch\Schema\Blueprint;

trait GrammarCompatibility
{
    private function createBlueprint(Blueprint $blueprint): Blueprint
    {
        if (Helpers::getLaravelCompatabilityVersion() >= 12) {
            return new Blueprint($blueprint->getConnection(), '');
        }

        return new Blueprint(''); // @phpstan-ignore arguments.count
    }
}
