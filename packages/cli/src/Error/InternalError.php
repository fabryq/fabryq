<?php

/**
 * Internal CLI error.
 *
 * @package   Fabryq\Cli\Error
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Error;

/**
 * Signals unexpected internal failures.
 */
final class InternalError extends \RuntimeException
{
}
