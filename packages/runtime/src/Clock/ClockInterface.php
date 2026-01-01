<?php

/**
 * Clock abstraction for runtime timestamps.
 *
 * @package   Fabryq\Runtime\Clock
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Clock;

use DateTimeImmutable;

/**
 * Provides a stable clock interface for deterministic time sources.
 */
interface ClockInterface
{
    /**
     * Return the current time.
     *
     * @return DateTimeImmutable Current timestamp.
     */
    public function now(): DateTimeImmutable;
}
