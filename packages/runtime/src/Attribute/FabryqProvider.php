<?php

/**
 * Attribute that marks a class as a Fabryq capability provider.
 *
 * @package Fabryq\Runtime\Attribute
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Attribute;

/**
 * Declares capability provider metadata for runtime registration.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class FabryqProvider
{
    public const PRIORITY_DEGRADED = -1000;

    /**
     * @param string $capability Capability identifier.
     * @param string $contract   Contract interface name.
     * @param int    $priority   Provider priority (higher wins).
     */
    public function __construct(
        public string $capability,
        public string $contract,
        public int $priority = 0,
    ) {}
}
