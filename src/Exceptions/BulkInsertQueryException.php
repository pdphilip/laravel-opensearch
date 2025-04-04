<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Exceptions;

class BulkInsertQueryException extends LaravelOpenSearchException
{
    private int $errorLimit = 10;

    /**
     * BulkInsertQueryException constructor.
     */
    public function __construct($queryResult)
    {
        parent::__construct($this->formatMessage($queryResult), 400);
    }

    /**
     * Format the error message.
     *
     * Takes the first {$this->errorLimit} bulk issues and concatenates them to a single string message
     */
    private function formatMessage(array $result): string
    {
        $message = collect();

        // Clean that ish up.
        $items = collect($result['items'] ?? [])
            ->filter(function (array $item) {
                return $item['index'] && ! empty($item['index']['error']);
            })
            ->map(function (array $item) {
                return $item['index'];
            })
        // reduce to max limit
            ->slice(0, $this->errorLimit)
            ->values();

        $totalErrors = collect($result['items'] ?? []);

        $message->push('Bulk Insert Errors ('.'Showing '.$items->count().' of '.$totalErrors->count().'):');

        $items = $items->map(function (array $item) {
            return "{$item['_id']}: {$item['error']['reason']}";
        })->values()->toArray();

        $message->push(...$items);

        return $message->implode(PHP_EOL);
    }
}
