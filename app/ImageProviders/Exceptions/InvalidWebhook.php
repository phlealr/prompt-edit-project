<?php

namespace App\ImageProviders\Exceptions;

use RuntimeException;

/**
 * A webhook request whose signature, timestamp or body cannot be trusted (ADR 0010).
 */
class InvalidWebhook extends RuntimeException {}
