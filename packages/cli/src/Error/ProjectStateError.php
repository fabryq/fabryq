<?php

/**
 * Project state error.
 *
 * @package   Fabryq\Cli\Error
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Error;

/**
 * Signals missing or invalid project state (files, manifests, dirs).
 */
final class ProjectStateError extends \RuntimeException
{
}
