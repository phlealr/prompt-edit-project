<?php

namespace App\ImageProviders\Exceptions;

use RuntimeException;

/**
 * The provider's pricing could not be fetched, and none is cached.
 */
class PricingUnavailable extends RuntimeException {}
