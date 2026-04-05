<?php

namespace PDPhilip\OpenSearch\Laravel\Compatibility\Connection;

use PDPhilip\Elasticsearch\Utils\Helpers;
use PDPhilip\OpenSearch\Query;
use PDPhilip\OpenSearch\Schema;

trait ConnectionCompatibility
{
    /**
     * @return Schema\Grammars\Grammar
     */
    public function getSchemaGrammar()
    {
        return new Schema\Grammars\Grammar(...$this->grammarArgs());
    }

    /** {@inheritdoc} */
    protected function getDefaultQueryGrammar(): Query\Grammar
    {
        return new Query\Grammar(...$this->grammarArgs());
    }

    /** {@inheritdoc} */
    protected function getDefaultSchemaGrammar(): Schema\Grammars\Grammar
    {
        return new Schema\Grammars\Grammar(...$this->grammarArgs());
    }

    /** @phpstan-ignore return.type */
    private function grammarArgs(): array
    {
        return Helpers::getLaravelCompatabilityVersion() >= 12 ? [$this] : [];
    }
}
