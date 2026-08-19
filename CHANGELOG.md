# Changelog

All notable changes to this `laravel-opensearch` package will be documented in this file.

## v3.2.0 - 2026-08-19

This release is compatible with Laravel 11, 12 & 13, and with OpenSearch 2.x & 3.x

### Added

- **OpenSearch 3.x support** - the full suite now runs green against OpenSearch 2.13, 2.19 and 3.8.
  Scrolling was the only thing in the way (see Fixed)
- **`cursor()` returns a `LazyCollection`** - matching Laravel's own `Query\Builder::cursor()` and
  `Eloquent\Builder::cursor()`, so `cursor()->chunk()`, `->map()`, `->filter()` and `->remember()`
  all work instead of fataling with `Call to undefined method Generator::chunk()`. Thanks to
  [@BobbyBorisov](https://github.com/BobbyBorisov) -
  [#28](https://github.com/pdphilip/laravel-opensearch/pull/28)
- CI now runs both OpenSearch lines (2.19.6 and 3.8.0) across PHP 8.3/8.4 and Laravel 11/12/13

### Fixed

- **Scrolling on OpenSearch 3.x** - `cursor()` and `chunk()` released their scroll context through
  the client's deprecated url-path branch, which `rawurlencode`s the scroll id. OpenSearch 3.x
  rejects the result (`Cannot parse scroll id` / `Illegal base64 character 25`) where 2.x tolerated
  it. Both paths now drive the scroll directly and keep the id in the request body. It only bit when
  the base64 id happened to contain a character needing encoding, so it read as intermittent
- **`cursor()` crashed on a fresh connection** - `Processor::getRawResponse()` dereferenced a null
  raw response (`Call to a member function asArray() on null`). Only select, insert and aggregate
  processing assign one, and a scroll runs none of them, so any cursor that was the first query on
  its connection died. Every existing cursor test inserted first, which is why nothing caught it
- **Scroll contexts are always released** - a `finally` block clears the scroll even when a consumer
  abandons the cursor early, which the previous path did not guarantee

### Changed

- **`cursor()` now returns `LazyCollection` instead of `Generator`.** `LazyCollection` is an
  `IteratorAggregate`, not an `Iterator`, so anything type-hinting `Generator` or `Iterator` against
  the old return value needs updating. Iterating with `foreach` is unaffected
- A cursor is now re-iterable - a second pass opens a fresh scroll rather than throwing
  `Cannot rewind a generator`
- Dropped the deprecated `SearchResponseIterator` and `SearchHitIterator` helpers, ahead of their
  removal in `opensearch-php` 3.0.0
- CI pins the OpenSearch service image. `:latest` silently became 3.8.0 and turned every job red at
  once with no package change
- PHPStan baseline down to 24 entries from 28 (the deprecated iterator suppressions are no longer
  needed)

> **Laravel 11 notice:** Laravel 11 is past its security window, and three advisories now cover the
> entire 11.x branch with no 11.x release that clears them (the fixes only ever landed on 12.x and
> 13.x). Composer 2.9 and up block advisory-affected versions while resolving, so on Laravel 11
> `composer require` and `composer update` will refuse to install anything, this package included.
> `composer install` from an existing lock file is unaffected, so deployments keep working.
>
> This package still supports and tests Laravel 11, and will keep doing so. If you need to install
> or update on it, `composer config policy.advisories.block false` lifts the block. The real fix is
> moving to Laravel 12 or 13.

**Full Changelog**: https://github.com/pdphilip/laravel-opensearch/compare/v3.1.0...v3.2.0

## v3.1.0 - 2026-04-06

> **Future-proofing note:** After GitHub incorrectly shadow-banned my account (since reinstated with no
> explanation beyond "it shouldn't happen again"), all packages are now mirrored to GitLab. GitHub
> remains the home for issues, PRs, and community collaboration, but **Packagist downloads exclusively
from GitLab** to ensure uninterrupted access to releases regardless of GitHub's actions.

This release is compatible with Laravel 11, 12 & 13

### Added

- **Laravel 13 support** (including 13.3 `HasCollection` compatibility)
- **Query String Queries** - `searchQueryString()`, `orSearchQueryString()`, `searchNotQueryString()`,
  `orSearchNotQueryString()` with full `QueryStringOptions` support -
  [Docs](https://opensearch.pdphilip.com/eloquent/query-string-queries)
- **Track Total Hits** - `withTrackTotalHits(bool|int|null)` to override the default 10k hit count cap
- [Docs](https://opensearch.pdphilip.com/eloquent/the-base-model/#track-total-hits)
- **Create or Fail** - `createOrFail()` throws `BulkInsertQueryException` (409) on duplicate IDs
  instead of upserting - [Docs](https://opensearch.pdphilip.com/eloquent/saving-models/#create-or-fail)
- **Set Refresh Flag** - `withRefresh(true|false|'wait_for')` to control index refresh behavior on
  writes - [Docs](https://opensearch.pdphilip.com/eloquent/saving-models/#with-refresh)
- **Create Only** - `createOnly()` and `withOpType('create')` for dedupe insert semantics with
  per-document `_op_type` support
- **Time-Ordered IDs** - `GeneratesTimeOrderedIds` trait for sortable, chronologically-ordered
  20-character IDs - [Docs](https://opensearch.pdphilip.com/eloquent/the-base-model/#3-generatestimeorderedids-trait)
- `QueryStringOptions` and `SimpleQueryStringOptions` classes
- Composer test scripts: `composer test:l11`, `composer test:l12`, `composer test:l13`, `composer
  test:all`

### Fixed

- **QueryException** crash on `Undefined array key "error"` when OpenSearch returns responses without
  the expected `error.type` structure
- **BulkInsertQueryException** crash on `op_type=create` - bulk response uses `create` key, not
  `index`; now uses `array_key_first()` with proper 409 status code inference
- **Field mapping resolution** - `getFieldsMapping()` now uses `_mapping` API instead of
  `_mapping/field/*` (OpenSearch PHP client returns empty for wildcard field queries)
- **`hasColumns()`** - iterates individual field checks instead of comma-separated query (same
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
- PHPStan baseline regenerated (36 entries, down from 61 - opensearch-php class casing fix)

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
