<img align="left" width="70" height="70" src="https://cdn.snipform.io/pdphilip/opensearch/laravel_opensearch.png">

# Laravel-OpenSearch

[![Latest Stable Version](http://img.shields.io/github/release/pdphilip/laravel-opensearch.svg)](https://packagist.org/packages/pdphilip/opensearch)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/laravel-opensearch/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pdphilip/laravel-opensearch/actions/workflows/run-tests.yml?query=branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/laravel-opensearch/phpstan.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pdphilip/laravel-opensearch/actions/workflows/phpstan.yml?query=branch%3Amain++)
[![Total Downloads](http://img.shields.io/packagist/dm/pdphilip/opensearch.svg)](https://packagist.org/packages/pdphilip/opensearch)

[OpenSearch](https://opensearch.net/) is a distributed, community-driven, Apache 2.0-licensed, 100% open-source search and analytics suite used for a broad set of use cases like real-time application monitoring, log analytics, and website
search.

### An OpenSearch implementation of Laravel's Eloquent ORM

### The Power of OpenSearch with Laravel's Eloquent

This package extends Laravel's Eloquent model and query builder with seamless integration of OpenSearch functionalities. Designed to feel native to Laravel, this package enables you to work with Eloquent models while leveraging the
powerful search and analytics capabilities of OpenSearch.

The Eloquent you already know:

```php
UserLog::where('created_at','>=',Carbon::now()->subDays(30))->get();
```

```php
UserLog::create([
    'user_id' => '2936adb0-b10d-11ed-8e03-0b234bda3e12',
    'ip' => '62.182.98.146',
    'location' => [40.7185,-74.0025],
    'country_code' => 'US',
    'status' => 1,
]);
```

```php
UserLog::where('status', 1)->update(['status' => 4]);
```

```php
UserLog::where('status', 4)->orderByDesc('created_at')->paginate(50);
```

```php
UserProfile::whereIn('country_code',['US','CA'])
    ->orderByDesc('last_login')->take(10)->get();
```

```php
UserProfile::where('state','unsubscribed')
    ->where('updated_at','<=',Carbon::now()->subDays(90))->delete();
```

opensearch with Eloquent:

```php
UserProfile::searchTerm('Laravel')->orSearchTerm('opensearch')->get();
```

```php
UserProfile::searchPhrasePrefix('loves espressos and t')->highlight()->get();
```

```php
UserProfile::whereMatch('bio', 'PHP')->get();
```

```php
UserLog::whereGeoDistance('location', '10km', [40.7185,-74.0025])->get();
```

```php
UserProfile::whereFuzzy('description', 'qick brwn fx')->get();
```

Built in Relationships (even to SQL models):

```php
UserLog::where('status', 1)->orderByDesc('created_at')->with('user')->get();
```

---

# Read the [Documentation](https://opensearch.pdphilip.com/)

## Installation

**Laravel 10.x, 11.x & 12.x (main):**

```bash
composer require pdphilip/opensearch
```

| Laravel Version    | Command                                    | Maintained |
|--------------------|--------------------------------------------|------------|
| Laravel 10/11/12   | `composer require pdphilip/opensearch:~3 ` | ✅          |
| Laravel 10/11 (v2) | `composer require pdphilip/opensearch:~2 ` | ❌ EOL      |
| Laravel 8 & 9      | `composer require pdphilip/opensearch:~1`  | ❌ EOL      |

## Configuration

1. Set up your `.env` with the following OpenSearch settings:

```ini
OS_HOSTS="http://opensearch:9200"
OS_USERNAME=
OS_PASSWORD=
OS_INDEX_PREFIX=my_app_
# prefix will be added to all indexes created by the package with an underscore
# ex: my_app_user_logs for UserLog.php model
    
# AWS SigV4 Config:
OS_SIG_V4_PROVIDER=
OS_SIG_V4_REGION=
OS_SIG_V4_SERVICE=

# Cert Config:
OS_SSL_CERT=
OS_SSL_CERT_PASSWORD=
OS_SSL_KEY=
OS_SSL_KEY_PASSWORD=

# Optional Settings:
OS_OPT_VERIFY_SSL=true
OS_OPT_RETRIES=
OS_OPT_SNIFF_ON_START=
OS_OPT_PORT_HOST_HEADERS=
OS_OPT_ID_SORTABLE=false
OS_OPT_META_HEADERS=true
OS_OPT_BYPASS_MAP_VALIDATION=false
OS_OPT_DEFAULT_LIMIT=1000
```

For multiple nodes, pass in as comma-separated:

```ini
OS_HOSTS="http://opensearch-node1:9200,http://opensearch-node2:9200,http://opensearch-node3:9200"
```

2. In `config/database.php`, add the OpensSearch connection:

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
        'bypass_map_validation' => env('OS_OPT_BYPASS_MAP_VALIDATION', false),
        'ssl_verification'      => env('OS_OPT_VERIFY_SSL', true),
        'retires'               => env('OS_OPT_RETRIES',null),
        'sniff_on_start'        => env('OS_OPT_SNIFF_ON_START',false),
        'logging'               => env('OS_OPT_LOGGING', false),
        'port_in_host_header'   => env('OS_OPT_PORT_HOST_HEADERS',false),
        'default_limit'         => env('OS_OPT_DEFAULT_LIMIT', 1000),
        'allow_id_sort'         => env('OS_OPT_ID_SORTABLE', false),
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

### Getting Started

- [Installation](https://opensearch.pdphilip.com/getting-started)
- [Configuration](https://opensearch.pdphilip.com/getting-started#configuration-guide)

### Eloquent

- [The Base Model](https://opensearch.pdphilip.com/eloquent/the-base-model)
- [Saving Models](https://opensearch.pdphilip.com/eloquent/saving-models)
- [Deleting Models](https://opensearch.pdphilip.com/eloquent/deleting-models)
- [Querying Models](https://opensearch.pdphilip.com/eloquent/querying-models)
- [Eloquent Queries](https://opensearch.pdphilip.com/eloquent/eloquent-queries)
- [OS Eloquent Queries](https://opensearch.pdphilip.com/eloquent/os-queries)
- [Cross Fields Search Queries](https://opensearch.pdphilip.com/eloquent/search-queries)
- [Aggregation Queries](https://opensearch.pdphilip.com/eloquent/aggregation)
- [Distinct and GroupBy Queries](https://opensearch.pdphilip.com/eloquent/distinct)
- [Nested Queries](https://opensearch.pdphilip.com/eloquent/nested-queries)
- [Ordering and Pagination](https://opensearch.pdphilip.com/eloquent/ordering-and-pagination)
- [Chunking](https://opensearch.pdphilip.com/eloquent/chunking)
- [Dynamic Indices](https://opensearch.pdphilip.com/eloquent/dynamic-indices)

### Relationships

- [OpenSearch to OpenSearch](https://opensearch.pdphilip.com/relationships/os-os)
- [OpenSearch to SQL](https://opensearch.pdphilip.com/relationships/os-sql)

### Migrations: Schema/Index

- [Migrations](https://opensearch.pdphilip.com/schema/migrations)
- [Index Blueprint](https://opensearch.pdphilip.com/schema/index-blueprint)

### Misc

- [Mapping OS to Eloquent](https://opensearch.pdphilip.com/notes/opensearch-to-eloquent-map)

## Credits

- [David Philip](https://github.com/pdphilip)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.