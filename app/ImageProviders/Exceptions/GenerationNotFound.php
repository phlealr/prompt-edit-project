<?php

namespace App\ImageProviders\Exceptions;

use RuntimeException;

/**
 * The provider does not know the generation we asked for (ADR 0027).
 */
class GenerationNotFound extends RuntimeException {}
