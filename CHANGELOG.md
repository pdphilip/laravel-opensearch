# Changelog

All notable changes to this `laravel-opensearch` package will be documented in this file.

## v3.1.0 - 2026-03-26

This release is compatible with Laravel 11, 12 & 13

### Added

- **Laravel 13 support**
- Composer test scripts for per-version testing: `composer test:l11`, `composer test:l12`, `composer test:l13`, `composer test:all`

### Changed

- Dropped Laravel 10 support (EOL)
- `pdphilip/elasticsearch` switched from `v5.0.4` (main) to `v0.5.2` (v0-core engine-only build — no ES PHP client bundled)
- `opensearch-project/opensearch-php` constraint relaxed from `2.3.1` to `^2.3`
- Removed `"replace": {"elasticsearch/elasticsearch": "*"}` (no longer needed with v0-core)
- CI matrix updated: PHP 8.3/8.4, Laravel 11/12/13

**Full Changelog**: https://github.com/pdphilip/laravel-opensearch/compare/v3.0.3...v3.1.0

## v3.0.3 - 2025-08-20

This release is compatible with Laravel 10, 11 & 12

### What's Changed

* Update Morphs Blueprints for Laravel ^12.23 Compatibility by @pbarsallo in https://github.com/pdphilip/laravel-opensearch/pull/20

### New Contributors

* @pbarsallo made their first contribution in https://github.com/pdphilip/laravel-opensearch/pull/20

### Bugfix

* fixed methods `processBulkInsert()`, `rawAggregation()` & `rawDsl()` - close #19

**Full Changelog**: https://github.com/pdphilip/laravel-opensearch/compare/v3.0.2...v3.0.3

## v3.0.2 - 2025-07-13

This release is compatible with Laravel 10, 11 & 12

### What's Changed

- Connection bug fix - Type casting for `default limit` and `retries`

**Full Changelog**: https://github.com/pdphilip/laravel-opensearch/compare/v3.0.1...v3.0.2

## v3.0.1 - 2025-06-04

This release is compatible with Laravel 10, 11 & 12

What's Changed
Bug fix: Chunking $count value fixed for setting query limit correctly, via https://github.com/pdphilip/laravel-elasticsearch/issues/68

**Full Changelog**: https://github.com/pdphilip/laravel-opensearch/compare/v3.0.0...v3.0.1
