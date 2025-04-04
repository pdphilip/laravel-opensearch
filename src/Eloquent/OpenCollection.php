<?php

namespace PDPhilip\OpenSearch\Eloquent;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use PDPhilip\OpenSearch\Data\MetaDTO;
use PDPhilip\OpenSearch\Data\QueryMeta;
use PDPhilip\OpenSearch\Eloquent\Model as TModel;

/**
 * @template TKey of array-key
 * @template TModel of \PDPhilip\OpenSearch\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Collection<int, TModel>
 */
class OpenCollection extends Collection
{
    protected ?QueryMeta $meta;

    /**
     * @param  Arrayable<TKey, TModel>|iterable<TKey, TModel>|array<TKey|int, mixed>|null  $items
     */
    public function __construct($items = [])
    {
        parent::__construct($items);
        //        $this->meta = new QueryMeta;
    }

    public static function loadCollection(Collection $collection)
    {
        return new static($collection->all());
    }

    public function loadMeta(QueryMeta $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function setQueryMeta(MetaDTO $meta): self
    {
        $this->meta = new QueryMeta($meta);

        return $this;
    }

    public function getQueryMeta(): QueryMeta
    {
        return $this->meta;
    }

    public function getQueryMetaAsArray(): array
    {
        return $this->meta->asArray();
    }

    public function getDsl(): array
    {
        return [
            'query' => $this->meta->getQuery(),
            'dsl' => $this->meta->getDsl(),
        ];
    }

    public function getTook(): int
    {
        return $this->meta->getTook();
    }

    public function getShards(): mixed
    {
        return $this->meta->getShards();
    }

    public function getTotal(): int
    {
        return $this->meta->getTotal();
    }

    public function getMaxScore(): string
    {
        return $this->meta->getMaxScore();
    }

    public function getResults(): array
    {
        return $this->meta->getResults();
    }

    public function getPitId()
    {
        return $this->meta->getPitId();
    }

    public function getAfterKey()
    {
        return $this->meta->getAfterKey();
    }
}
