<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use OpenSearch\Common\Exceptions\BadRequest400Exception;
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

it('handles error responses without error.type structure', function () {
    $response = json_encode(['status' => 400, 'message' => 'Something went wrong']);
    $previous = new BadRequest400Exception($response, 400);

    $exception = new QueryException($previous);

    expect($exception)->toBeInstanceOf(QueryException::class)
        ->and($exception->getMessage())->toBe($response);
});

it('handles error responses with null json body', function () {
    $previous = new BadRequest400Exception('not json at all', 400);

    $exception = new QueryException($previous);

    expect($exception)->toBeInstanceOf(QueryException::class)
        ->and($exception->getMessage())->toBe('not json at all');
});
