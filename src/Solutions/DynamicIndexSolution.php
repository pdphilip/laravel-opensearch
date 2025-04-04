<?php

namespace PDPhilip\OpenSearch\Solutions;

use Spatie\Ignition\Contracts\Solution;

class DynamicIndexSolution implements Solution
{
    public function __construct(public $modelName) {}

    public function getSolutionTitle(): string
    {
        return 'Use DynamicIndex trait in '.$this->modelName.' model';
    }

    public function getSolutionDescription(): string
    {
        return "```php

class {$this->modelName} extends Model
{
    use DynamicIndex;


";
    }

    public function getDocumentationLinks(): array
    {
        return [
            'Read more' => 'https://opensearch.pdphilip.com/eloquent/dynamic-indices/',
        ];
    }
}
