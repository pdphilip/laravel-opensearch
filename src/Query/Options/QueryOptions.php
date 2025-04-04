<?php

namespace PDPhilip\OpenSearch\Query\Options;

use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * @mixin MatchOptions
 * @mixin TermOptions
 * @mixin FuzzyOptions
 * @mixin SearchOptions
 * @mixin DateOptions
 * @mixin PhraseOptions
 * @mixin PhrasePrefixOptions
 * @mixin PrefixOptions
 * @mixin RegexOptions
 */
abstract class QueryOptions extends Fluent
{
    public function __call($method, $parameters)
    {
        $key = Str::snake($method);

        // Let ES validate the option
        //        if (! in_array($key, $this->allowedOptions(), true)) {
        //            throw new \InvalidArgumentException("Option '{$key}' is not allowed for this query type.");
        //        }

        $this->{$key} = $parameters[0];

        return $this;
    }

    abstract public function allowedOptions(): array;
}
