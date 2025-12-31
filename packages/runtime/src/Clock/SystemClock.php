<?php

/**
 * System clock implementation.
 *
 * @package Fabryq\Runtime\Clock
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Clock;

/**
 * Returns system time for the current process.
 */
final readonly class SystemClock implements ClockInterface
{
    /**
     * {@inheritDoc}
     */
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }
}
