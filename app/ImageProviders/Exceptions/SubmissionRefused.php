<?php

namespace App\ImageProviders\Exceptions;

use RuntimeException;

/**
 * The provider refused the submission for a reason waiting cannot fix: its daily limit, our key,
 * the request, or the idempotency key (ADR 0022). The message names the provider's answer.
 */
class SubmissionRefused extends RuntimeException {}
