<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Traits;

use PDPhilip\OpenSearch\Contracts\ArrayStore as ArrayStoreContract;
use PDPhilip\OpenSearch\Repositories\ArrayStore;

trait HasOptions
{
    /**
     * Request Config
     */
    protected ArrayStoreContract $options;

    /**
     * Access the config
     */
    public function options(): ArrayStoreContract
    {
        return $this->options ??= new ArrayStore($this->defaultConfig());
    }

    /**
     * Default Config
     *
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [];
    }
}
