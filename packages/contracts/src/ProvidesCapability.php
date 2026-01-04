<?php

/**
 * Value object for a manifest capability provide entry.
 *
 * @package   Fabryq\Contracts
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts;

use Fabryq\Contracts\Exception\InvalidManifestException;

/**
 * Immutable declaration of a provided capability and its contract.
 */
final readonly class ProvidesCapability
{
    /**
     * @param string $capabilityId Capability identifier provided by the app.
     * @param string $contract     Contract identifier for the capability.
     */
    public function __construct(
        /**
         * Capability identifier.
         *
         * @var string
         */
        public string $capabilityId,
        /**
         * Contract identifier.
         *
         * @var string
         */
        public string $contract,
    ) {
    }

    /**
     * Create an instance from a manifest entry.
     *
     * @param mixed $entry Manifest entry.
     *
     * @return self
     * @throws InvalidManifestException When the entry is invalid.
     */
    public static function fromMixed(mixed $entry): self
    {
        if (!is_array($entry)) {
            throw new InvalidManifestException('Manifest "provides" entries must be arrays.');
        }

        if (!isset($entry['capabilityId']) || !isset($entry['contract'])) {
            throw new InvalidManifestException('Manifest "provides" entry missing "capabilityId" or "contract".');
        }

        return new self((string) $entry['capabilityId'], (string) $entry['contract']);
    }
}
