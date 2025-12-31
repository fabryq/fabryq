<?php

/**
 * ULID factory backed by Symfony UID.
 *
 * @package Fabryq\Runtime\Ulid
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Ulid;

use Symfony\Component\Uid\Ulid;

/**
 * Generates ULIDs using Symfony's implementation.
 */
final readonly class SymfonyUlidFactory implements UlidFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(): Ulid
    {
        return new Ulid();
    }
}
