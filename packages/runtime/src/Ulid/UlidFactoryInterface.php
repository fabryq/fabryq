<?php

/**
 * ULID factory abstraction.
 *
 * @package   Fabryq\Runtime\Ulid
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Ulid;

use Symfony\Component\Uid\Ulid;

/**
 * Creates ULID identifiers.
 */
interface UlidFactoryInterface
{
    /**
     * Create a new ULID.
     *
     * @return Ulid New ULID instance.
     */
    public function create(): Ulid;
}
