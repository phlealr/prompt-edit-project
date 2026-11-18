<?php

namespace App\Enums;

/**
 * Where a generation is in its lifecycle; it only moves forward, in this order (ADR 0025).
 */
enum GenerationStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
