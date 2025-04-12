<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Exceptions;

use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Solutions\DynamicIndexSolution;
use Spatie\Ignition\Contracts\ProvidesSolution;
use Spatie\Ignition\Contracts\Solution;
use Throwable;

class DynamicIndexException extends LaravelOpenSearchException implements ProvidesSolution
{
    public $modelName = '';

    public function __construct($message, Model $model, Throwable $previous)
    {
        $this->modelName = class_basename($model);
        parent::__construct($this->formatMessage($message), 400, $previous);
    }

    private function formatMessage($message): string
    {
        return $message." - Dynamic Index trait not set for model: {$this->modelName}";

    }

    public function getSolution(): Solution
    {
        return new DynamicIndexSolution($this->modelName);
    }
}
