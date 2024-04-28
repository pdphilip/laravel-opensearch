# Laravel-OpenSearch

**This package has been built off the back of the original [Elasticsearch version](https://github.com/pdphilip/laravel-elasticsearch) of this package since v3.11.0**

### An OpenSearch implementation of Laravel's Eloquent ORM

This package extends Laravel's Eloquent model and query builder with seamless integration of OpenSearch functionalities. Designed to feel native to Laravel, this package enables you to work with Eloquent models while leveraging the
powerful search and analytics capabilities of OpenSearch.

```php
$logs = UserLog::where('type', UserLogType::LOGIN)->where('created_at','>=',Carbon::now()->subDays(30))->get();
```

### Read the [Documentation](https://elasticsearch.pdphilip.com/)

Note: This package will receive its own version of this documentation. In the meantime, you can reference the Elasticsearch documentation and replace all namespaces  `PDPhilip\Elasticsearch\...` with `PDPhilip\OpenSearch\...`

---

## Installation

**Laravel 11.x (main):**

```bash
composer require pdphilip/opensearch
```

## Configuration

1. Set up your `.env` with the following OpenSearch settings:

```ini
OS_HOSTS="http://opensearch:9200"
OS_USERNAME=
OS_PASSWORD=
OS_INDEX_PREFIX=my_app

OS_SIG_V4_PROVIDER=
OS_SIG_V4_REGION=
OS_SIG_V4_SERVICE=

OS_SSL_CERT=
OS_SSL_CERT_PASSWORD=
OS_SSL_KEY=
OS_SSL_KEY_PASSWORD=

OS_OPT_VERIFY_SSL=true
OS_OPT_RETRIES=
OS_OPT_SNIFF_ON_START=
OS_OPT_PORT_HOST_HEADERS=
```

For multiple nodes, pass in as comma-separated:

```ini
ES_HOSTS="http://opensearch-node1:9200,http://opensearch-node2:9200,http://opensearch-node3:9200"
```

2. In `config/database.php`, add the opensearch connection:

```php
'opensearch' => [
    'driver'       => 'opensearch',
    'hosts'        => explode(',', env('OS_HOSTS', 'http://localhost:9200')),
    'basic_auth'   => [
        'username' => env('OS_USERNAME', ''),
        'password' => env('OS_PASSWORD', ''),
    ],
    'sig_v4'       => [
        'provider' => env('OS_SIG_V4_PROVIDER'),
        'region'   => env('OS_SIG_V4_REGION'),
        'service'  => env('OS_SIG_V4_SERVICE'),
    ],
    'ssl'          => [
        'cert'          => env('OS_SSL_CERT', ''),
        'cert_password' => env('OS_SSL_CERT_PASSWORD', ''),
        'key'           => env('OS_SSL_KEY', ''),
        'key_password'  => env('OS_SSL_KEY_PASSWORD', ''),
    ],
    'index_prefix' => env('OS_INDEX_PREFIX', false),
    'options'      => [
        'ssl_verification'    => env('OS_OPT_VERIFY_SSL', true),
        'retires'             => env('OS_OPT_RETRIES'),
        'sniff_on_start'      => env('OS_OPT_SNIFF_ON_START'),
        'port_in_host_header' => env('OS_OPT_PORT_HOST_HEADERS'),
    ],
    'query_log'    => [
        'index'      => false, //Or provide a name for the logging index ex: 'laravel_query_logs'
        'error_only' => true, //If false, then all queries are logged if the query_log index is set
    ],
],
```

### 3. If packages are not autoloaded, add the service provider:

For **Laravel 10 and below**:

```php
//config/app.php
'providers' => [
    ...
    ...
    PDPhilip\OpenSearch\OpenSearchServiceProvider::class,
    ...

```

For **Laravel 11**:

```php
//bootstrap/providers.php
<?php
return [
    App\Providers\AppServiceProvider::class,
    PDPhilip\OpenSearch\OpenSearchServiceProvider::class,
];
```

Now, you're all set to use OpenSearch with Laravel as if it were native to the framework.

---

# Documentation Links

## Getting Started

- [Installation](https://elasticsearch.pdphilip.com/#installation)
- [Configuration](https://elasticsearch.pdphilip.com/#configuration)

## Eloquent

- [The Base Model](https://elasticsearch.pdphilip.com/the-base-model)
- [Querying Models](https://elasticsearch.pdphilip.com/querying-models)
- [Saving Models](https://elasticsearch.pdphilip.com/saving-models)
- [Deleting Models](https://elasticsearch.pdphilip.com/deleting-models)
- [Ordering and Pagination](https://elasticsearch.pdphilip.com/ordering-and-pagination)
- [Distinct and GroupBy](https://elasticsearch.pdphilip.com/distinct)
- [Aggregations](https://elasticsearch.pdphilip.com/aggregation)
- [Chunking](https://elasticsearch.pdphilip.com/chunking)
- [Nested Queries](https://elasticsearch.pdphilip.com/nested-queries) **New in Version 3**
- [Elasticsearch Specific Queries](https://elasticsearch.pdphilip.com/es-specific)
- [Full-Text Search](https://elasticsearch.pdphilip.com/full-text-search)
- [Dynamic Indices](https://elasticsearch.pdphilip.com/dynamic-indices)

## Relationships

- [Elasticsearch to Elasticsearch](https://elasticsearch.pdphilip.com/es-es)
- [Elasticsearch to MySQL](https://elasticsearch.pdphilip.com/es-mysql)

## Schema/Index

- [Migrations](https://elasticsearch.pdphilip.com/migrations)
- [Re-indexing Process](https://elasticsearch.pdphilip.com/re-indexing)

---


---
