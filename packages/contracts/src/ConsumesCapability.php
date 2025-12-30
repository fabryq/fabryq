<?php

/**
 * Value object for a manifest capability consumption entry.
 *
 * @package   Fabryq\Contracts
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts;

use Fabryq\Contracts\Exception\InvalidManifestException;

/**
 * Immutable declaration of a consumed capability and its contract.
 *
 * Responsibilities:
 * - Capture required capability metadata for a manifest.
 */
final readonly class ConsumesCapability
{
    /**
     * @param string      $capabilityId Capability identifier to consume.
     * @param bool        $required     Whether the capability is mandatory.
     * @param string|null $contract     [Optional] Contract identifier or null when unspecified.
     */
    public function __construct(
        /**
         * Capability identifier required by the app.
         *
         * @var string
         */
        public string  $capabilityId,
        /**
         * Whether the capability must be satisfied.
         *
         * @var bool
         */
        public bool    $required,
        /**
         * Contract identifier for the capability, when provided.
         *
         * @var string|null
         */
        public ?string $contract,
    ) {}

    /**
     * Create an instance from a mixed manifest entry.
     *
     * @param mixed $entry String capability ID or an array with capability metadata.
     *
     * @return self Normalized capability consumption entry.
     * @throws InvalidManifestException When the entry is not a valid capability definition.
     *
     */
    public static function fromMixed(mixed $entry): self
    {
        if (is_string($entry)) {
            return new self($entry, true, null);
        }

        if (!is_array($entry)) {
            throw new InvalidManifestException('Manifest "consumes" entries must be strings or arrays.');
        }

        if (!isset($entry['capabilityId'])) {
            throw new InvalidManifestException('Manifest "consumes" entry missing "capabilityId".');
        }

        $required = array_key_exists('required', $entry) ? (bool)$entry['required'] : true;
        $contract = isset($entry['contract']) ? (string)$entry['contract'] : null;

        return new self((string)$entry['capabilityId'], $required, $contract);
    }
}
