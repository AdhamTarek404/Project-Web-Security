<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use RuntimeException;

// Thrown when code tries an illegal state move, e.g. delivered → preparing.
// We extend RuntimeException so it bubbles up cleanly and any API endpoint
// that catches it can map it to a 422.
class InvalidOrderTransitionException extends RuntimeException
{
    public function __construct(
        public readonly OrderStatus $from,
        public readonly OrderStatus $to,
    ) {
        parent::__construct(
            "Cannot transition order from [{$from->value}] to [{$to->value}]."
        );
    }
}
