<?php

/**
 * ULID factory abstraction.
 *
 * @package Fabryq\Runtime\Ulid
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Ulid;

use Symfony\Component\Uid\Ulid;

/**
 * Creates ULIDs for runtime usage.
 */
interface UlidFactoryInterface
{
    /**
     * Create a new ULID.
     *
     * @return Ulid Newly generated ULID.
     */
    public function create(): Ulid;
}
