<?php

namespace App\ImageProviders\Exceptions;

use RuntimeException;

/**
 * The provider is rate limited, at its concurrency limit or overloaded: nothing was created, and
 * waiting can fix it (ADR 0022).
 */
class ProviderBusy extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds, string $message)
    {
        parent::__construct($message);
    }
}
