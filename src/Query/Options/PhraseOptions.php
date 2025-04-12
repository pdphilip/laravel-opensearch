<?php

namespace PDPhilip\OpenSearch\Query\Options;

/**
 * @method $this analyzer(string $analyzer)
 * @method $this slop(int $value)
 * @method $this zeroTermsQuery(string $value)
 */
class PhraseOptions extends QueryOptions
{
    public function allowedOptions(): array
    {
        return [
            'analyzer',
            'slop',
            'zero_terms_query',
        ];
    }
}
