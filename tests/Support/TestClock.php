<?php

/**
 * Fixed clock for tests.
 *
 * @package   Fabryq\Tests\Support
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Support;

use DateTimeImmutable;
use Fabryq\Runtime\Clock\ClockInterface;

/**
 * Returns a constant timestamp.
 */
final class TestClock implements ClockInterface
{
    /**
     * @param DateTimeImmutable $now Fixed timestamp.
     */
    public function __construct(
        private DateTimeImmutable $now
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
