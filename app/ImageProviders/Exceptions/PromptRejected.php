<?php

namespace App\ImageProviders\Exceptions;

use RuntimeException;

/**
 * The provider refused the prompt under its content policy; nothing was created (ADR 0022).
 */
class PromptRejected extends RuntimeException {}
