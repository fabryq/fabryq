<?php

/**
 * Value object for an application manifest definition.
 *
 * @package   Fabryq\Contracts
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
final readonly class Manifest
{
    /**
 * @param string                $appId      Application identifier used as a stable key.
 * @param string                $name       Human-readable application name.
 * @param string|null           $mountpoint [Optional] Mountpoint path or null when not mounted.
 * @param ProvidesCapability[]  $provides   Declared capability providers.
 * @param ConsumesCapability[]  $consumes   Declared capability requirements.
 * @param ManifestEvents|null   $events     [Optional] Event publication/subscription metadata.
 */
    public function __construct(
        /**
         * Stable application identifier.
         *
         * @var string
         */
        public string          $appId,
        /**
         * Display name of the application.
         *
         * @var string
         */
        public string          $name,
        /**
         * Mountpoint path, or null when the app does not provide one.
         *
         * @var string|null
         */
        public ?string         $mountpoint,
        /**
         * Capabilities provided by the application.
         *
         * @var ProvidesCapability[]
         */
        public array           $provides,
        /**
         * Capabilities consumed by the application.
         *
         * @var ConsumesCapability[]
         */
        public array           $consumes,
        /**
         * Event declarations for the application, when provided.
         *
         * @var ManifestEvents|null
         */
        public ?ManifestEvents $events,
    ) {
    }

    /**
     * Create a manifest from a normalized array payload.
     *
     * @param array<string, mixed> $data Manifest data including required keys.
     *
     * @return self Fully constructed manifest value object.
     * @throws InvalidManifestException When required keys are missing or values are invalid.
     *
     */
    public static function fromArray(array $data): self
    {
        foreach (['appId', 'name', 'mountpoint', 'consumes'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new InvalidManifestException(sprintf('Missing manifest key "%s".', $requiredKey));
            }
        }

        $provides = [];
        if (isset($data['provides'])) {
            if (!is_array($data['provides'])) {
                throw new InvalidManifestException('Manifest "provides" must be an array.');
            }
            foreach ($data['provides'] as $entry) {
                $provides[] = ProvidesCapability::fromMixed($entry);
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
            (string)$data['appId'],
            (string)$data['name'],
            $data['mountpoint'] === null ? null : (string)$data['mountpoint'],
            $provides,
            $consumes,
            $events,
        );
    }
}
