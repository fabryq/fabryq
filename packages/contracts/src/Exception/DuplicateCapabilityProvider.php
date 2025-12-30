<?php

/**
 * Exception definition for duplicate capability providers.
 *
 * @package Fabryq\Contracts\Exception
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts\Exception;

/**
 * Exception thrown when multiple providers claim the same capability contract.
 *
 * Invariants:
 * - The message identifies the conflicting capability and providers when available.
 */
final class DuplicateCapabilityProvider extends \RuntimeException
{
}
