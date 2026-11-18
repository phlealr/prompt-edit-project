<?php

namespace App\ImageProviders\Exceptions;

use RuntimeException;

/**
 * The request to the provider failed on the network, timed out, or got a server error, so the
 * provider may or may not have created the generation (ADR 0022, ADR 0040, ADR 0043).
 */
class ProviderUnreachable extends RuntimeException {}
