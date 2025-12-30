<?php

/**
 * Exception definition for missing required capabilities.
 *
 * @package Fabryq\Contracts\Exception
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts\Exception;

/**
 * Exception thrown when a required capability cannot be satisfied.
 *
 * Invariants:
 * - The message indicates the missing capability identifier.
 */
final class MissingRequiredCapability extends \RuntimeException
{
}
