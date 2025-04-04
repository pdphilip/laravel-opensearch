<?php

namespace PDPhilip\OpenSearch\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use PDPhilip\OpenSearch\Helpers\Helpers;

trait GeneratesUuids
{
    use HasUuids;

    public function initializeGeneratesUuids()
    {
        $this->generatesUniqueIds = true;
    }

    public function newUniqueId(): string
    {
        return Helpers::uuid();
    }
}
