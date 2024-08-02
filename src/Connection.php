<?php

namespace PDPhilip\OpenSearch;

use PDPhilip\OpenSearch\DSL\Bridge;

use OpenSearch\ClientBuilder;
use OpenSearch\Client;

use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Support\Str;
use RuntimeException;


class Connection extends BaseConnection
{

    protected $client;
    protected $index;
    protected $maxSize;
    protected $indexPrefix;
    protected $sslVerification = true;
    protected $retires = null;
    protected $sniff = null;
    protected $portInHeaders = null;
    protected $rebuild = false;
    protected $allowIdSort = true;
    protected $errorLoggingIndex = false;
    protected $connectionName = 'opensearch';

    public function __construct(array $config)
    {

        $this->connectionName = $config['name'];

        $this->config = $config;

        $this->setOptions($config);

        $this->client = $this->buildConnection();

        $this->useDefaultPostProcessor();

        $this->useDefaultSchemaGrammar();

        $this->useDefaultQueryGrammar();

    }

    public function setOptions($config)
    {
        if (!empty($config['index_prefix'])) {
            $this->indexPrefix = $config['index_prefix'];
        }
        if (isset($config['options']['ssl_verification'])) {
            $this->sslVerification = $config['options']['ssl_verification'];
        }
        if (isset($config['options']['retires'])) {
            $this->retires = $config['options']['retires'];
        }
        if (isset($config['options']['sniff_on_start'])) {
            $this->sniff = $config['options']['sniff_on_start'];
        }
        if (isset($config['options']['port_in_host_header'])) {
            $this->portInHeaders = $config['options']['port_in_host_header'];
        }
        if (!empty($config['error_log_index'])) {
            if ($this->indexPrefix) {
                $this->errorLoggingIndex = $this->indexPrefix.'_'.$config['error_log_index'];
            } else {
                $this->errorLoggingIndex = $config['error_log_index'];
            }
        }
    }

    public function getIndexPrefix(): string|null
    {
        return $this->indexPrefix;
    }

    public function setIndexPrefix($newPrefix): void
    {
        $this->indexPrefix = $newPrefix;
    }


    public function getTablePrefix(): string|null
    {
        return $this->getIndexPrefix();
    }

    public function setIndex($index): string
    {
        $this->index = $index;
        if ($this->indexPrefix) {
            if (!(str_contains($this->index, $this->indexPrefix.'_'))) {
                $this->index = $this->indexPrefix.'_'.$index;
            }
        }

        return $this->getIndex();
    }

    public function getErrorLoggingIndex(): string|bool
    {
        return $this->errorLoggingIndex;
    }

    public function getSchemaGrammar()
    {
        return new Schema\Grammar($this);
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function setMaxSize($value)
    {
        $this->maxSize = $value;
    }


    public function table($table, $as = null)
    {
        $query = new Query\Builder($this, new Query\Processor());

        return $query->from($table);
    }

    /**
     * @inheritdoc
     */
    public function getSchemaBuilder()
    {
        return new Schema\Builder($this);
    }


    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        unset($this->connection);
    }


    /**
     * @inheritdoc
     */
    public function getDriverName(): string
    {
        return 'opensearch';
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processor();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultQueryGrammar()
    {
        return new Query\Grammar();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultSchemaGrammar()
    {
        return new Schema\Grammar();
    }

    public function rebuildConnection()
    {
        $this->rebuild = true;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getMaxSize()
    {
        return $this->maxSize;
    }

    public function getAllowIdSort()
    {
        return $this->allowIdSort;
    }


    //----------------------------------------------------------------------
    // Connection Builder
    //----------------------------------------------------------------------

    protected function buildConnection(): Client
    {
        $hosts = config('database.connections.'.$this->connectionName.'.hosts') ?? null;

        $builder = ClientBuilder::create()->setHosts($hosts);
        $builder = $this->_buildOptions($builder);
        $builder = $this->_buildAuth($builder);
        $builder = $this->_buildSigV4($builder);
        $builder = $this->_buildSSL($builder);

        return $builder->build();

    }

    protected function _buildAuth(ClientBuilder $builder): ClientBuilder
    {

        $username = config('database.connections.'.$this->connectionName.'.basic_auth.username') ?? null;
        $pass = config('database.connections.'.$this->connectionName.'.basic_auth.password') ?? null;
        if ($username && $pass) {
            $builder->setBasicAuthentication($username, $pass);
        }

        return $builder;
    }

    protected function _buildSigV4(ClientBuilder $builder): ClientBuilder
    {
        $provider = config('database.connections.'.$this->connectionName.'.sig_v4.provider') ?? null;
        $region = config('database.connections.'.$this->connectionName.'.sig_v4.region') ?? null;
        $service = config('database.connections.'.$this->connectionName.'.sig_v4.service') ?? null;
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
        $sslCert = config('database.connections.'.$this->connectionName.'.ssl.cert') ?? null;
        $sslCertPassword = config('database.connections.'.$this->connectionName.'.ssl.cert_password') ?? null;
        $sslKey = config('database.connections.'.$this->connectionName.'.ssl.key') ?? null;
        $sslKeyPassword = config('database.connections.'.$this->connectionName.'.ssl.key_password') ?? null;
        if ($sslCert) {
            $builder->setSSLCert($sslCert, $sslCertPassword);
        }
        if ($sslKey) {
            $builder->setSSLKey($sslKey, $sslKeyPassword);
        }

        return $builder;
    }

    protected function _buildOptions(ClientBuilder $builder): ClientBuilder
    {
        $builder->setSSLVerification($this->sslVerification);
        if (!empty($this->retires)) {
            $builder->setRetries($this->retires);
        }
        if (!empty($this->sniff)) {
            $builder->setSniffOnStart($this->sniff);
        }
        if (!empty($this->portInHeaders)) {
            $builder->includePortInHostHeader($this->portInHeaders);
        }

        return $builder;
    }




    //----------------------------------------------------------------------
    // Dynamic call routing to DSL bridge
    //----------------------------------------------------------------------

    public function __call($method, $parameters)
    {
        if (!$this->index) {
            $this->index = $this->indexPrefix.'*';
        }
        if ($this->rebuild) {
            $this->client = $this->buildConnection();
            $this->rebuild = false;
        }
        $bridge = new Bridge($this);

        return $bridge->{'process'.Str::studly($method)}(...$parameters);
    }
}
