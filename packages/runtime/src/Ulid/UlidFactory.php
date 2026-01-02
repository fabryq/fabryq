<?php

/**
 * Default ULID factory.
 *
 * @package   Fabryq\Runtime\Ulid
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Ulid;

use Symfony\Component\Uid\Ulid;

/**
 * Creates ULIDs using the Symfony UID component.
 */
final class UlidFactory implements UlidFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(): Ulid
    {
        return new Ulid();
    }
}
