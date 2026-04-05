# Changelog

All notable changes to this `laravel-opensearch` package will be documented in this file.

## v3.1.0 - 2026-04-06

> **Future-proofing note:** After GitHub incorrectly shadow-banned my account (since reinstated with no
> explanation beyond "it shouldn't happen again"), all packages are now mirrored to GitLab. GitHub
> remains the home for issues, PRs, and community collaboration, but **Packagist downloads exclusively
from GitLab** to ensure uninterrupted access to releases regardless of GitHub's actions.

This release is compatible with Laravel 11, 12 & 13

### Added

- **Laravel 13 support** (including 13.3 `HasCollection` compatibility)
- **Query String Queries** — `searchQueryString()`, `orSearchQueryString()`, `searchNotQueryString()`,
  `orSearchNotQueryString()` with full `QueryStringOptions` support -
  [Docs](https://opensearch.pdphilip.com/eloquent/query-string-queries)
- **Track Total Hits** — `withTrackTotalHits(bool|int|null)` to override the default 10k hit count cap
- [Docs](https://opensearch.pdphilip.com/eloquent/the-base-model/#track-total-hits)
- **Create or Fail** — `createOrFail()` throws `BulkInsertQueryException` (409) on duplicate IDs
  instead of upserting - [Docs](https://opensearch.pdphilip.com/eloquent/saving-models/#create-or-fail)
- **Set Refresh Flag** — `withRefresh(true|false|'wait_for')` to control index refresh behavior on
  writes - [Docs](https://opensearch.pdphilip.com/eloquent/saving-models/#with-refresh)
- **Create Only** — `createOnly()` and `withOpType('create')` for dedupe insert semantics with
  per-document `_op_type` support
- **Time-Ordered IDs** — `GeneratesTimeOrderedIds` trait for sortable, chronologically-ordered
  20-character IDs - [Docs](https://opensearch.pdphilip.com/eloquent/the-base-model/#3-generatestimeorderedids-trait)
- `QueryStringOptions` and `SimpleQueryStringOptions` classes
- Composer test scripts: `composer test:l11`, `composer test:l12`, `composer test:l13`, `composer
  test:all`

### Fixed

- **QueryException** crash on `Undefined array key "error"` when OpenSearch returns responses without
  the expected `error.type` structure
- **BulkInsertQueryException** crash on `op_type=create` — bulk response uses `create` key, not
  `index`; now uses `array_key_first()` with proper 409 status code inference
- **Field mapping resolution** — `getFieldsMapping()` now uses `_mapping` API instead of
  `_mapping/field/*` (OpenSearch PHP client returns empty for wildcard field queries)
- **`hasColumns()`** — iterates individual field checks instead of comma-separated query (same
  OpenSearch PHP client limitation)
- Geo bounding box test (wrong field name + double `.get()` call)

### Changed

- Dropped Laravel 10 support (EOL)
- PHP minimum bumped from 8.2 to 8.3
- Compat layer refactored: consolidated `Laravel/v11/` and `Laravel/v12/` directories into 4
  self-contained traits in `Laravel/Compatibility/` using inline version checks with spread operators
- `newCollection()` override on base Model to prevent L13.3 `HasCollection` abstract class
  instantiation
- `opensearch-project/opensearch-php` updated to `^2.6`
- PHPStan baseline regenerated (36 entries, down from 61 — opensearch-php class casing fix)

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
