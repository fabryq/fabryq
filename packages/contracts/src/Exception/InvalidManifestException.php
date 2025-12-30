<?php

/**
 * Exception definition for invalid manifest data.
 *
 * @package Fabryq\Contracts\Exception
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts\Exception;

/**
 * Exception thrown when a manifest payload cannot be validated or normalized.
 *
 * Invariants:
 * - The message describes the validation failure.
 */
final class InvalidManifestException extends \RuntimeException
{
}
