<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Exceptions;

use Exception;

class MissingOrderException extends LaravelOpenSearchException
{
    //    private array $_details;

    public function __construct($message = 'Order parameter is required for pagination using search_after.', $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
