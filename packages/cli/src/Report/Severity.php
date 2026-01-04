<?php

/**
 * Severity levels for CLI findings and reports.
 *
 * @package   Fabryq\Cli\Report
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Report;

/**
 * Supported severity labels for findings.
 */
final class Severity
{
    public const BLOCKER = 'BLOCKER';
    public const WARNING = 'WARNING';

    private function __construct()
    {
    }
}
