<?php

/**
 * Value object for an application manifest definition.
 *
 * @package Fabryq\Contracts
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts;

use Fabryq\Contracts\Exception\InvalidManifestException;

/**
 * Immutable manifest definition for an application.
 *
 * Responsibilities:
 * - Hold normalized manifest metadata.
 * - Provide construction from array data with validation.
 */
final class Manifest
{
    /**
     * @param string $appId Application identifier used as a stable key.
     * @param string $name Human-readable application name.
     * @param string|null $mountpoint [Optional] Mountpoint path or null when not mounted.
     * @param ConsumesCapability[] $consumes Declared capability requirements.
     * @param ManifestEvents|null $events [Optional] Event publication/subscription metadata.
     */
    public function __construct(
        /**
         * Stable application identifier.
         *
         * @var string
         */
        public readonly string $appId,
        /**
         * Display name of the application.
         *
         * @var string
         */
        public readonly string $name,
        /**
         * Mountpoint path, or null when the app does not provide one.
         *
         * @var string|null
         */
        public readonly ?string $mountpoint,
        /**
         * Capabilities consumed by the application.
         *
         * @var ConsumesCapability[]
         */
        public readonly array $consumes,
        /**
         * Event declarations for the application, when provided.
         *
         * @var ManifestEvents|null
         */
        public readonly ?ManifestEvents $events,
    ) {
    }

    /**
     * Create a manifest from a normalized array payload.
     *
     * @param array<string, mixed> $data Manifest data including required keys.
     *
     * @throws InvalidManifestException When required keys are missing or values are invalid.
     *
     * @return self Fully constructed manifest value object.
     */
    public static function fromArray(array $data): self
    {
        foreach (['appId', 'name', 'mountpoint', 'consumes'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new InvalidManifestException(sprintf('Missing manifest key "%s".', $requiredKey));
            }
        }

        $consumes = [];
        if (!is_array($data['consumes'])) {
            throw new InvalidManifestException('Manifest "consumes" must be an array.');
        }

        foreach ($data['consumes'] as $entry) {
            $consumes[] = ConsumesCapability::fromMixed($entry);
        }

        $events = null;
        if (isset($data['events']) && is_array($data['events'])) {
            $events = ManifestEvents::fromArray($data['events']);
        }

        return new self(
            (string) $data['appId'],
            (string) $data['name'],
            $data['mountpoint'] === null ? null : (string) $data['mountpoint'],
            $consumes,
            $events,
        );
    }
}
