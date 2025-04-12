<?php

namespace PDPhilip\OpenSearch\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use PDPhilip\Elasticsearch\Utils\TimeBasedUUIDGenerator;

trait GeneratesElasticIds
{
    use HasUuids;

    public function initializeGeneratesElasticIds()
    {
        $this->generatesUniqueIds = true;
    }

    public function newUniqueId(): string
    {
        return TimeBasedUUIDGenerator::generate();
    }
}
