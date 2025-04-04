<?php

namespace PDPhilip\OpenSearch\Query\Options;

/**
 * @method $this boost(float|int $value)
 */
class TermsOptions extends QueryOptions
{
    public function allowedOptions(): array
    {
        return [
            'boost',
        ];
    }
}
