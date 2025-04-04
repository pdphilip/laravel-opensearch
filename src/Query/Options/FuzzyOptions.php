<?php

namespace PDPhilip\OpenSearch\Query\Options;

/**
 * @method $this fuzziness(string|int $value)
 * @method $this maxExpansions(int $value)
 * @method $this prefixLength(int $value)
 * @method $this transpositions(bool $value)
 * @method $this rewrite(string $value)
 */
class FuzzyOptions extends QueryOptions
{
    public function allowedOptions(): array
    {
        return [
            'fuzziness',
            'max_expansions',
            'prefix_length',
            'transpositions',
            'rewrite',
        ];
    }
}
