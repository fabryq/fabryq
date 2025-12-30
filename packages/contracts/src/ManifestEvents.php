<?php

/**
 * Value object for manifest event publish/subscribe declarations.
 *
 * @package Fabryq\Contracts
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts;

/**
 * Immutable set of event names used by an app manifest.
 *
 * Responsibilities:
 * - Track event names the app publishes and subscribes to.
 */
final class ManifestEvents
{
    /**
     * @param string[] $publishes Event names this app publishes.
     * @param string[] $subscribes Event names this app subscribes to.
     */
    public function __construct(
        /**
         * Event names emitted by the app.
         *
         * @var string[]
         */
        public readonly array $publishes,
        /**
         * Event names the app listens for.
         *
         * @var string[]
         */
        public readonly array $subscribes,
    ) {
    }

    /**
     * Build an instance from a manifest array.
     *
     * Non-string entries are ignored to keep the output normalized.
     *
     * @param array<string, mixed> $data Manifest data with optional "publishes" and "subscribes" keys.
     *
     * @return self Normalized manifest events value object.
     */
    public static function fromArray(array $data): self
    {
        $publishes = array_values(array_filter($data['publishes'] ?? [], 'is_string'));
        $subscribes = array_values(array_filter($data['subscribes'] ?? [], 'is_string'));

        return new self($publishes, $subscribes);
    }
}
