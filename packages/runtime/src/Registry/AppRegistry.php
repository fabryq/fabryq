<?php

/**
 * Registry container for discovered applications and validation issues.
 *
 * @package   Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

/**
 * Holds application definitions and related validation issues.
 */
final readonly class AppRegistry
{
    /**
     * @param AppDefinition[]   $apps   Application definitions.
     * @param ValidationIssue[] $issues Validation issues collected during discovery.
     */
    public function __construct(
        /**
         * Discovered application definitions.
         *
         * @var AppDefinition[]
         */
        private array $apps,
        /**
         * Validation issues encountered during discovery.
         *
         * @var ValidationIssue[]
         */
        private array $issues,
    ) {
    }

    /**
     * Find an application by its manifest appId.
     *
     * @param string $appId Manifest application identifier.
     *
     * @return AppDefinition|null Matching application or null when not found.
     */
    public function findById(string $appId): ?AppDefinition
    {
        foreach ($this->apps as $app) {
            if ($app->manifest->appId === $appId) {
                return $app;
            }
        }

        return null;
    }

    /**
     * Return all discovered applications.
     *
     * @return AppDefinition[]
     */
    public function getApps(): array
    {
        return $this->apps;
    }

    /**
     * Return validation issues from discovery.
     *
     * @return ValidationIssue[]
     */
    public function getIssues(): array
    {
        return $this->issues;
    }
}
