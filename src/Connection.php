<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch;

use Closure;
use Exception;
use Generator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use OpenSearch\Helper\Iterators\SearchHitIterator;
use OpenSearch\Helper\Iterators\SearchResponseIterator;
use OpenSearch\Namespaces\IndicesNamespace;
use PDPhilip\Elasticsearch\Traits\HasOptions;
use PDPhilip\OpenSearch\Exceptions\BulkInsertQueryException;
use PDPhilip\OpenSearch\Exceptions\QueryException;
use PDPhilip\OpenSearch\Laravel\Compatibility\Connection\ConnectionCompatibility;
use PDPhilip\OpenSearch\Query\Builder;
use PDPhilip\OpenSearch\Query\Processor;
use PDPhilip\OpenSearch\Schema\Blueprint;

use function array_replace_recursive;
use function is_array;

/**
 * @mixin Client
 *
 * @method Processor getPostProcessor()
 */
class Connection extends BaseConnection
{
    use ConnectionCompatibility;
    use HasOptions;

    /**
     * The OpenSearch connection handler.
     */
    protected ?OpenClient $connection;

    protected string $connectionName = '';

    /**
     * @var Query\Processor
     */
    protected $postProcessor;

    protected $requestTimeout;

    public $allowIdSort = false;

    public $defaultQueryLimit = 1000;

    /** {@inheritdoc}
     * @throws AuthenticationException
     */
    public function __construct(array $config)
    {
        $this->connectionName = $config['name'];

        $this->config = $config;

        $this->sanitizeConfig();

        $this->setOptions();

        $this->connection = $this->createConnection();

        $this->postProcessor = new Query\Processor;

        $this->useDefaultSchemaGrammar();

        $this->useDefaultQueryGrammar();

        if (! empty($this->config['index_prefix'])) {
            $this->setIndexPrefix($this->config['index_prefix']);
        }
    }

    // ----------------------------------------------------------------------
    // Connection Setup
    // ----------------------------------------------------------------------

    /**
     * Sanitizes the configuration array by merging it with a predefined array of default configuration settings.
     * This ensures that all required configuration keys exist, even if they are set to null or default values.
     */
    private function sanitizeConfig(): void
    {

        $this->config = array_replace_recursive(
            [
                'name' => null,
                'hosts' => [],
                'basic_auth' => [
                    'username' => null,
                    'password' => null,
                ],
                'sig_v4' => [
                    'provider' => null,
                    'region' => null,
                    'service' => null,
                ],
                'ssl' => [
                    'key' => null,
                    'key_password' => null,
                    'cert' => null,
                    'cert_password' => null,
                ],
                'index_prefix' => '',
                'options' => [
                    'port_in_host_header' => null,
                    'sniff_on_start' => null,
                    'bypass_map_validation' => false, // This skips the safety checks for mapping validation.
                    'logging' => false,
                    'ssl_verification' => true,
                    'retires' => null,
                    'default_limit' => null,
                    'allow_id_sort' => false,
                ],
                'connection_params' => [],
            ],
            $this->config
        );

    }

    public function setOptions(): void
    {
        $this->allowIdSort = $this->config['options']['allow_id_sort'] ?? false;
        $this->options()->add('bypass_map_validation', $this->config['options']['bypass_map_validation'] ?? false);

        if (! empty($this->config['options']['retires'])) {
            $this->options()->add('retires', (int) $this->config['options']['retires']);
        }

        if (isset($this->config['options']['meta_header'])) {
            $this->options()->add('meta_header', $this->config['options']['meta_header']);
        }

        if (isset($this->config['options']['default_limit'])) {
            $this->defaultQueryLimit = (int) $this->config['options']['default_limit'];
        }
    }

    // ----------------------------------------------------------------------
    // Connection Builder
    // ----------------------------------------------------------------------

    protected function createConnection(): OpenClient
    {
        $builder = ClientBuilder::create()->setHosts($this->config['hosts']);
        $builder = $this->_builderOptions($builder);
        $builder = $this->_buildAuth($builder);
        $builder = $this->_buildSigV4($builder);
        $builder = $this->_buildSSL($builder);

        return new OpenClient($builder->build());

    }

    protected function _buildAuth(ClientBuilder $builder): ClientBuilder
    {
        $username = $this->config['basic_auth']['username'];
        $password = $this->config['basic_auth']['password'];
        if ($username && $password) {
            $builder->setBasicAuthentication($username, $password);
        }

        return $builder;
    }

    protected function _buildSigV4(ClientBuilder $builder): ClientBuilder
    {
        $provider = $this->config['sig_v4']['provider'];
        $region = $this->config['sig_v4']['region'];
        $service = $this->config['sig_v4']['service'];
        if ($provider) {
            $builder->setSigV4CredentialProvider($provider);
        }
        if ($region) {
            $builder->setSigV4Region($region);
        }
        if ($service) {
            $builder->setSigV4Service($service);
        }

        return $builder;
    }

    protected function _buildSSL(ClientBuilder $builder): ClientBuilder
    {
        $sslCert = $this->config['ssl']['cert'];
        $sslCertPassword = $this->config['ssl']['cert_password'];
        $sslKey = $this->config['ssl']['key'];
        $sslKeyPassword = $this->config['ssl']['key_password'];
        if ($sslCert) {
            $builder->setSSLCert($sslCert, $sslCertPassword);
        }
        if ($sslKey) {
            $builder->setSSLKey($sslKey, $sslKeyPassword);
        }

        return $builder;
    }

    protected function _builderOptions(ClientBuilder $builder): ClientBuilder
    {
        $builder->setSSLVerification($this->config['options']['ssl_verification']);

        if ($this->config['options']['port_in_host_header'] !== null) {
            $builder->includePortInHostHeader((bool) $this->config['options']['port_in_host_header']);
        }
        if ($this->config['options']['sniff_on_start'] !== null) {
            $builder->setSniffOnStart((bool) $this->config['options']['sniff_on_start']);
        }

        return $builder;
    }

    /** {@inheritdoc} */
    public function disconnect(): void
    {
        $this->connection = $this->createConnection();
    }

    // ----------------------------------------------------------------------
    // Connection getters
    // ----------------------------------------------------------------------
    public function getClient(): ?OpenClient
    {
        return $this->connection;
    }

    public function getIndexPrefix(): string
    {
        return $this->getTablePrefix();
    }

    public function getClientInfo(): array
    {
        return $this->openClient()->info();
    }

    /** {@inheritdoc} */
    public function getDriverName(): string
    {
        return 'opensearch';
    }

    /**
     * @return Schema\Builder
     */
    public function getSchemaBuilder()
    {
        return new Schema\Builder($this);
    }

    /** {@inheritdoc} */
    protected function getDefaultPostProcessor(): Query\Processor
    {
        return new Query\Processor;
    }

    public function getDefaultLimit(): int
    {
        return $this->defaultQueryLimit;
    }

    // ----------------------------------------------------------------------
    // Connection Setters
    // ----------------------------------------------------------------------

    public function setIndexPrefix($prefix): self
    {
        return $this->setTablePrefix($prefix);
    }

    /**
     * Set the timeout for the entire OpenSearch request
     *
     * @param  float  $requestTimeout  seconds
     */
    public function setRequestTimeout(float $requestTimeout): self
    {
        $this->requestTimeout = $requestTimeout;

        return $this;
    }

    // ----------------------------------------------------------------------
    // Schema Management
    // ----------------------------------------------------------------------

    public function createAlias(string $index, string $name): void
    {
        $this->connection->createAlias($index, $name);
    }

    /**
     * @throws QueryException
     */
    public function createIndex(string $index, array $body): array
    {
        try {
            $this->connection->createIndex($index, $body);

            return $this->connection->getMappings($index);
        } catch (Exception $e) {
            throw new QueryException($e, compact('index', 'body'));
        }
    }

    public function dropIndex(string $index): void
    {
        $this->connection->dropIndex($index);
    }

    public function updateIndex(string $index, array $body): array
    {
        $this->connection->updateIndex($index, $body);

        return $this->connection->getMappings($index);
    }

    public function getFieldMapping($index, $fields): array
    {
        return $this->connection->getFieldMapping($index, $fields);
    }

    public function getMappings($index): array
    {
        return $this->connection->getMappings($index);
    }

    public function indices(): IndicesNamespace
    {
        return $this->connection->indices();
    }

    // ----------------------------------------------------------------------
    // Query Execution
    // ----------------------------------------------------------------------

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array  $bindings
     */
    public function statement($query, $bindings = [], ?Blueprint $blueprint = null): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $this->bindValues($query, $this->prepareBindings($bindings));

            $this->recordsHaveBeenModified();

            return $query();
        });
    }

    /**
     * Run a select statement against the database and return a generator.
     *
     * @param  array  $query
     * @param  string  $scrollTimeout
     * @param  int  $size
     */
    public function searchResponseIterator($query, $scrollTimeout = '30s', $size = 100): Generator
    {

        $scrollParams = [
            'scroll' => $scrollTimeout,
            'size' => $size, // Number of results per shard
            'index' => $query['index'],
            'body' => $query['body'],
        ];

        $pages = new SearchResponseIterator($this->connection, $scrollParams);
        foreach ($pages as $page) {
            yield $page;
        }
    }

    /**
     * Run a select statement against the database and return a generator.
     *
     * @param  array  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @param  string  $scrollTimeout
     */
    public function cursor($query, $bindings = [], $useReadPdo = false, $scrollTimeout = '30s')
    {

        $limit = is_array($query) && isset($query['body']['size']) ? $query['body']['size'] : null;

        // We want to scroll by 1000 row chunks
        $query['body']['size'] = 1000;

        $scrollParams = [
            'scroll' => $scrollTimeout,
            'index' => $query['index'],
            'body' => $query['body'],
        ];

        $count = 0;
        $pages = new SearchResponseIterator($this->openClient(), $scrollParams);
        $hits = new SearchHitIterator($pages);

        foreach ($hits as $hit) {
            $count++;
            if ($count > $limit) {
                break;
            }
            yield $hit;
        }

        return (function () {
            yield;
        })();
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return array
     */
    public function delete($query, $bindings = [])
    {
        return $this->run(
            $query,
            $bindings,
            $this->connection->deleteByQuery(...)
        );
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  array  $query
     * @param  array  $bindings
     *
     * @throws BulkInsertQueryException
     */
    public function insert($query, $bindings = [], $continueWithErrors = false)
    {
        $result = $this->run(
            $this->addClientParams($query),
            $bindings,
            $this->connection->bulk(...)
        );

        if (! $continueWithErrors && ! empty($result['errors'])) {
            throw new BulkInsertQueryException($result);
        }

        return $result;
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string|array  $query
     * @param  array  $bindings
     * @param  float|null  $time
     */
    public function logQuery($query, $bindings, $time = null): void
    {
        if (is_array($query)) {
            $query = json_encode($query);
        }

        $this->event(new QueryExecuted($query, $bindings, $time, $this));

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }

    /**
     * Prepare the query bindings for execution.
     */
    public function prepareBindings(array $bindings): array
    {
        return $bindings;
    }

    /**
     * Get a new query builder instance.
     */
    public function query(): Builder
    {
        return new Builder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    public function reconnectIfMissingConnection(): void
    {
        if (is_null($this->connection)) {
            $this->connection = $this->createConnection();
        }
    }

    /**
     * Run a select statement against the database.
     *
     * @param  array  $params
     * @param  array  $bindings
     */
    public function select($params, $bindings = [], $useReadPdo = true)
    {
        return $this->run(
            $this->addClientParams($params),
            $bindings,
            $this->connection->search(...)
        );
    }

    public function count($params): int
    {
        return $this->connection->count($params);
    }

    /**
     * Run an update statement against the database.
     *
     * @param  array  $query
     * @param  array  $bindings
     */
    public function update($query, $bindings = []): mixed
    {
        $updateMethod = isset($query['body']['query']) ? 'updateByQuery' : 'update';

        return $this->run(
            $query,
            $bindings,
            $this->connection->$updateMethod(...)
        );
    }

    public function raw($value)
    {
        return $this->connection->search($value);
    }

    /**
     * Add client-specific parameters to the request params
     */
    protected function addClientParams(array $params): array
    {
        if ($this->requestTimeout) {
            $params['client']['timeout'] = $this->requestTimeout;
        }

        return $params;
    }

    /** {@inheritdoc}
     * @throws QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        try {
            $result = $callback($query, $bindings);
        } catch (Exception $e) {
            throw new QueryException($e, $query);
        }

        return $result;
    }

    /**
     * @param  mixed  $query
     */
    protected function run($query, $bindings, Closure $callback): mixed
    {
        return parent::run($query, $bindings, $callback);
    }

    public function openPit(mixed $query): ?string
    {
        return $this->connection->openPit($query);
    }

    public function closePit(mixed $query): bool
    {
        return $this->connection->closePit($query);
    }

    // ----------------------------------------------------------------------
    // Direct Client Access and cluster methods
    // ----------------------------------------------------------------------

    public function openClient(): Client
    {
        return $this->connection->client();
    }

    public function clusterSettings($flat = true): array
    {
        return $this->connection->clusterSettings($flat);
    }

    public function setClusterFieldDataOnId(bool $enabled, bool $transient = false): array
    {
        return $this->connection->setClusterFieldDataOnId($enabled, $transient);
    }

    // ----------------------------------------------------------------------
    // Call Catch
    // ----------------------------------------------------------------------

    /**
     * Dynamically pass methods to the connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    //    public function __call($method, $parameters)
    //    {
    //        dd($method);
    //
    //        return call_user_func_array([$this->connection, $method], $parameters);
    //    }

    // ----------------------------------------------------------------------
    // Later/Maybe
    // ----------------------------------------------------------------------

    //    /**
    //     * Run a reindex statement against the database.
    //     *
    //     * @param  string|array  $query
    //     * @param  array  $bindings
    //     * @return array
    //     */
    //    public function reindex($query, $bindings = [])
    //    {
    //        return $this->run(
    //            $query,
    //            $bindings,
    //            $this->connection->reindex(...)
    //        )->asArray();
    //    }
}
