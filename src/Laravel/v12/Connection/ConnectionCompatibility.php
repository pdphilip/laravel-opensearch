<?php

namespace PDPhilip\OpenSearch\Laravel\v12\Connection;

use PDPhilip\OpenSearch\Query;
use PDPhilip\OpenSearch\Schema;

trait ConnectionCompatibility
{
    /**
     * @return Schema\Grammars\Grammar
     */
    public function getSchemaGrammar()
    {
        return new Schema\Grammars\Grammar($this);
    }

    /** {@inheritdoc} */
    protected function getDefaultQueryGrammar(): Query\Grammar
    {
        return new Query\Grammar($this);
    }

    /** {@inheritdoc} */
    protected function getDefaultSchemaGrammar(): Schema\Grammars\Grammar
    {
        return new Schema\Grammars\Grammar($this);
    }
}
