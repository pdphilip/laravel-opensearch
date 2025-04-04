<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use PDPhilip\OpenSearch\Exceptions\QueryException;
use PDPhilip\OpenSearch\Tests\Models\PageHit;

// Set up the database schema before each test
beforeEach(function () {
    PageHit::executeSchema();
});

it('tests first or fail', function () {
    $results = DB::table('users')->whereRaw(['foo'])->get();
})->throws(QueryException::class, 'Error Type: parsing_exception
Reason: Unknown key for a START_ARRAY in [query].


Root Cause Type: parsing_exception
Root Cause Reason: Unknown key for a START_ARRAY in [query]');
