<?php

/**
 * Markdown link builder for report locations.
 *
 * @package   Fabryq\Cli\Report
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Report;

use Fabryq\Cli\Config\ProjectConfig;

/**
 * Builds clickable Markdown links for findings.
 */
final class ReportLinkBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $linkConfig;

    /**
     * @param ProjectConfig $config     Project config.
     * @param string        $projectDir Absolute project directory.
     */
    public function __construct(
        ProjectConfig $config,
        private readonly string $projectDir,
    ) {
        $reports = $config->reports();
        $this->linkConfig = is_array($reports['links'] ?? null) ? $reports['links'] : [];
    }

    /**
     * Format a location with optional Markdown link.
     *
     * @param string|null $path Relative path.
     * @param int|null    $line Line number.
     *
     * @return string
     */
    public function format(?string $path, ?int $line): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        $location = $path;
        if ($line !== null && $line > 0) {
            $location .= ':' . $line;
        }

        $enabled = (bool) ($this->linkConfig['enabled'] ?? false);
        $scheme = (string) ($this->linkConfig['scheme'] ?? 'none');
        if (!$enabled || $scheme === 'none') {
            return $location;
        }

        $absPath = $this->resolveAbsolutePath($path);
        if ($absPath === null) {
            return $location;
        }

        $link = match ($scheme) {
            'phpstorm' => $this->buildPhpStormLink($absPath, $line),
            'file' => $this->buildFileLink($absPath),
            default => null,
        };

        if ($link === null) {
            return $location;
        }

        return sprintf('[%s](%s)', $location, $link);
    }

    /**
     * Resolve an absolute path from a relative path.
     *
     * @param string $path Relative path.
     *
     * @return string|null
     */
    private function resolveAbsolutePath(string $path): ?string
    {
        $candidate = rtrim($this->projectDir, '/') . '/' . ltrim($path, '/');
        $resolved = realpath($candidate);

        return $resolved === false ? null : $resolved;
    }

    /**
     * Build a PhpStorm open link.
     *
     * @param string   $path Absolute path.
     * @param int|null $line Line number.
     *
     * @return string
     */
    private function buildPhpStormLink(string $path, ?int $line): string
    {
        $query = 'file=' . $path;
        if ($line !== null && $line > 0) {
            $query .= '&line=' . $line;
        }

        return 'phpstorm://open?' . $query;
    }

    /**
     * Build a file:// link.
     *
     * @param string $path Absolute path.
     *
     * @return string
     */
    private function buildFileLink(string $path): string
    {
        return 'file://' . $path;
    }
}
