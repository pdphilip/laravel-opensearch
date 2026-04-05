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
        parent::__construct($this->formatMessage($queryResult), $this->inferStatusCode($queryResult));
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
                $action = array_key_first($item) ?? 'index';

                return isset($item[$action]) && ! empty($item[$action]['error']);
            })
            ->map(function (array $item) {
                $action = array_key_first($item) ?? 'index';

                return $item[$action];
            })
            // reduce to max limit
            ->slice(0, $this->errorLimit)
            ->values();

        $totalErrors = collect($result['items'] ?? []);

        $message->push('Bulk Insert Errors ('.'Showing '.$items->count().' of '.$totalErrors->count().'):');

        $items = $items->map(function (array $item) {
            $id = $item['_id'] ?? 'unknown';
            $reason = $item['error']['reason'] ?? 'unknown error';
            $type = $item['error']['type'] ?? 'error';

            return "$id: [$type] $reason";
        })->values()->toArray();

        $message->push(...$items);

        return $message->implode(PHP_EOL);
    }

    private function inferStatusCode(array $result): int
    {
        foreach ($result['items'] ?? [] as $item) {
            $action = array_key_first($item) ?? 'index';
            $error = $item[$action]['error'] ?? null;
            if (is_array($error) && ($error['type'] ?? '') === 'version_conflict_engine_exception') {
                return 409;
            }
        }

        return 400;
    }
}
