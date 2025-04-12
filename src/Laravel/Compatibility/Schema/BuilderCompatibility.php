<?php

namespace PDPhilip\OpenSearch\Laravel\Compatibility\Schema;

use PDPhilip\Elasticsearch\Utils\Helpers;
use PDPhilip\OpenSearch\Laravel\v11\Schema\BuilderCompatibility as BuilderCompatibility11;
use PDPhilip\OpenSearch\Laravel\v12\Schema\BuilderCompatibility as BuilderCompatibility12;

$laravelVersion = Helpers::getLaravelCompatabilityVersion();

if ($laravelVersion == 12) {
    trait BuilderCompatibility
    {
        use BuilderCompatibility12;
    }
} else {
    trait BuilderCompatibility
    {
        use BuilderCompatibility11;
    }
}
