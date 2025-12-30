<?php

/**
 * Utility for generating URL-safe component slugs.
 *
 * @package Fabryq\Runtime\Util
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Util;

/**
 * Converts component names into normalized, kebab-case slugs.
 */
final class ComponentSlugger
{
    /**
     * Convert a component name to a URL-safe slug.
     *
     * @param string $name Component name to normalize.
     *
     * @return string Normalized kebab-case slug.
     */
    public function slug(string $name): string
    {
        $slug = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $name);
        $slug = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $slug ?? $name);
        $slug = strtolower((string) $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug ?? '');
        $slug = preg_replace('/-+/', '-', $slug ?? '');

        return trim((string) $slug, '-');
    }
}
