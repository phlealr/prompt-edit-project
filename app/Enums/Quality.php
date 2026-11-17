<?php

namespace App\Enums;

/**
 * The detail level a user picks (ADR 0023).
 */
enum Quality: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
