<?php

/**
 * System clock implementation.
 *
 * @package   Fabryq\Runtime\Clock
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Clock;

use DateTimeImmutable;

/**
 * Returns the current system time.
 */
final class SystemClock implements ClockInterface
{
    /**
     * {@inheritDoc}
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
