<?php

/**
 * Fix mode constants.
 *
 * @package Fabryq\Cli\Fix
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Fix;

/**
 * Defines dry-run and apply modes.
 */
final class FixMode
{
    public const DRY_RUN = 'dry-run';
    public const APPLY = 'apply';
}
