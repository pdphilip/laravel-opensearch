<?php

namespace PDPhilip\OpenSearch;

use Illuminate\Support\Arr;
use Opensearch\Client;
use OpenSearch\Namespaces\IndicesNamespace;
use PDPhilip\OpenSearch\Query\DSL\DslBuilder;

class OpenClient
{
    public function __construct(protected Client $client) {}

    public function client(): Client
    {
        return $this->client;
    }

    public function search(array $params = [])
    {
        return $this->client->search($params);
    }

    public function count(array $params = []): int
    {
        return $this->client->count($params)['count'] ?? 0;
    }

    public function bulk(array $params = [])
    {
        return $this->client->bulk($params);
    }

    public function update(array $params = [])
    {
        return $this->client->update($params);
    }

    public function updateByQuery(array $params = [])
    {
        return $this->client->updateByQuery($params);
    }

    public function deleteByQuery(array $params = [])
    {
        return $this->client->deleteByQuery($params);
    }

    public function scroll(array $params = [])
    {
        return $this->client->scroll($params);
    }

    public function clearScroll(array $params = [])
    {
        return $this->client->clearScroll($params);
    }

    // ----------------------------------------------------------------------
    // Indices
    // ----------------------------------------------------------------------

    public function indices(): IndicesNamespace
    {
        return $this->client->indices();
    }

    public function getMappings(string $index): array
    {
        $params = ['index' => Arr::wrap($index)];

        return $this->client->indices()->getMapping($params);
    }

    public function createAlias(string $index, string $name)
    {
        return $this->client->indices()->putAlias(compact('index', 'name'));
    }

    public function createIndex(string $index, array $body)
    {
        return $this->client->indices()->create(compact('index', 'body'));
    }

    public function dropIndex(string $index)
    {
        return $this->client->indices()->delete(compact('index'));
    }

    public function updateIndex(string $index, array $body): void
    {
        if ($mappings = $body['mappings'] ?? null) {
            $this->client->indices()->putMapping(['index' => $index, 'body' => ['properties' => $mappings['properties']]]);
        }
        if ($settings = $body['settings'] ?? null) {
            $this->client->indices()->close(['index' => $index]);
            $this->client->indices()->putSettings(['index' => $index, 'body' => ['settings' => $settings]]);
            $this->client->indices()->open(['index' => $index]);
        }
    }

    public function getFieldMapping(string $index, string $fields): array
    {
        return $this->client->indices()->getFieldMapping(compact('index', 'fields'));
    }

    // ----------------------------------------------------------------------
    // PIT API
    // ----------------------------------------------------------------------

    public function openPit(array $params = []): ?string
    {
        $open = $this->client->createPit($params);

        return $open['pit_id'] ?? null;
    }

    public function closePit(array $params = []): bool
    {
        $closed = $this->client->deletePit($params);

        return $closed['succeeded'] ?? false;
    }

    // ----------------------------------------------------------------------
    // Cluster
    // ----------------------------------------------------------------------

    public function clusterSettings($flat = true): array
    {
        return $this->client->cluster()->getSettings(['flat_settings' => (bool) $flat]);
    }

    public function setClusterFieldDataOnId($enabled, $transient = false): array
    {
        $type = $transient ? 'transient' : 'persistent';
        $dsl = new DslBuilder;
        $dsl->setBody([$type, 'indices.id_field_data.enabled'], (bool) $enabled);

        return $this->client->cluster()->putSettings($dsl->getDsl());

    }
}
