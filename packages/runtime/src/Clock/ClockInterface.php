<?php

/**
 * Clock abstraction for Fabryq runtime.
 *
 * @package Fabryq\Runtime\Clock
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Clock;

/**
 * Provides current time without depending on global state.
 */
interface ClockInterface
{
    /**
     * Return the current time.
     *
     * @return \DateTimeImmutable Current timestamp.
     */
    public function now(): \DateTimeImmutable;
}
