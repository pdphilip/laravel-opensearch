<?php

namespace PDPhilip\OpenSearch\Laravel\v11\Schema;

use PDPhilip\OpenSearch\Connection;
use PDPhilip\OpenSearch\Schema\Grammars\Grammar;

trait BlueprintCompatibility
{
    // @phpstan-ignore-next-line
    public function build(Connection|\Illuminate\Database\Connection|null $connection = null, Grammar|\Illuminate\Database\Schema\Grammars\Grammar|null $grammar = null): void
    {
        foreach ($this->toDSL($connection, $grammar) as $statement) {
            if ($connection->pretending()) {
                return;
            }

            $statement($this, $connection);
        }
    }
}
