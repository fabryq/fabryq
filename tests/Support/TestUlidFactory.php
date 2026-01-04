<?php

/**
 * Deterministic ULID factory for tests.
 *
 * @package   Fabryq\Tests\Support
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Support;

use Fabryq\Runtime\Ulid\UlidFactoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Returns the same ULID value each time.
 */
final class TestUlidFactory implements UlidFactoryInterface
{
    /**
     * @param string $value ULID base32 string.
     */
    public function __construct(
        private string $value
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function create(): Ulid
    {
        return new Ulid($this->value);
    }
}
