<?php

namespace PDPhilip\OpenSearch\Laravel\Compatibility\Schema;

use PDPhilip\OpenSearch\Helpers\Helpers;
use PDPhilip\OpenSearch\Laravel\v11\Schema\BlueprintCompatibility as BlueprintCompatibility11;
use PDPhilip\OpenSearch\Laravel\v12\Schema\BlueprintCompatibility as BlueprintCompatibility12;

$laravelVersion = Helpers::getLaravelCompatabilityVersion();

if ($laravelVersion == 12) {
    trait BlueprintCompatibility
    {
        use BlueprintCompatibility12;
    }
} else {
    trait BlueprintCompatibility
    {
        use BlueprintCompatibility11;
    }
}
