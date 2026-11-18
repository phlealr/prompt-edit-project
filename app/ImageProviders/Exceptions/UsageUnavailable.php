<?php

namespace App\ImageProviders\Exceptions;

use RuntimeException;

/**
 * The provider's usage report could not be fetched for the whole range (ADR 0030).
 */
class UsageUnavailable extends RuntimeException {}
