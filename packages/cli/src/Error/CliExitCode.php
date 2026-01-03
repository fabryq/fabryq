<?php

/**
 * Canonical CLI exit codes for Fabryq commands.
 *
 * @package   Fabryq\Cli\Error
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Error;

/**
 * Exit code constants shared across commands.
 */
final class CliExitCode
{
    public const SUCCESS = 0;
    public const INTERNAL_ERROR = 1;
    public const USER_ERROR = 2;
    public const PROJECT_STATE_ERROR = 3;

    private function __construct()
    {
    }
}
