<?php

namespace PDPhilip\OpenSearch\Laravel\Compatibility\Connection;

use PDPhilip\OpenSearch\Helpers\Helpers;
use PDPhilip\OpenSearch\Laravel\v11\Connection\ConnectionCompatibility as ConnectionCompatibility11;
use PDPhilip\OpenSearch\Laravel\v12\Connection\ConnectionCompatibility as ConnectionCompatibility12;

$laravelVersion = Helpers::getLaravelCompatabilityVersion();

if ($laravelVersion == 12) {
    trait ConnectionCompatibility
    {
        use ConnectionCompatibility12;
    }
} else {
    trait ConnectionCompatibility
    {
        use ConnectionCompatibility11;
    }
}
