<?php

namespace PDPhilip\OpenSearch\Laravel\Compatibility\Schema;

use PDPhilip\Elasticsearch\Helpers\Helpers;
use PDPhilip\OpenSearch\Laravel\v11\Schema\GrammarCompatibility as GrammarCompatibility11;
use PDPhilip\OpenSearch\Laravel\v12\Schema\GrammarCompatibility as GrammarCompatibility12;

$laravelVersion = Helpers::getLaravelCompatabilityVersion();

if ($laravelVersion == 12) {
    trait GrammarCompatibility
    {
        use GrammarCompatibility12;
    }
} else {
    trait GrammarCompatibility
    {
        use GrammarCompatibility11;
    }
}
