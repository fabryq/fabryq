<?php

/**
 * Manifest discovery for applications under the project directory.
 *
 * @package Fabryq\Runtime\Discovery
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Discovery;

use Fabryq\Contracts\Manifest;
use Fabryq\Contracts\Exception\InvalidManifestException;
use Fabryq\Runtime\Registry\ValidationIssue;

/**
 * Finds and validates manifest files for applications.
 */
final class ManifestDiscovery
{
    /**
     * Discover applications by locating and parsing manifest files.
     *
     * Side effects:
     * - Reads manifest files from disk.
     *
     * @param string $projectDir Absolute project directory.
     *
     * @return array{apps: DiscoveredManifest[], issues: ValidationIssue[]}
     */
    public function discover(string $projectDir): array
    {
        $appsDir = rtrim($projectDir, '/').'/src/Apps';
        if (!is_dir($appsDir)) {
            return ['apps' => [], 'issues' => []];
        }

        $issues = [];
        $apps = [];
        $mountpoints = [];

        foreach (glob($appsDir.'/*/manifest.php') ?: [] as $manifestPath) {
            $appPath = dirname($manifestPath);
            $appFolder = basename($appPath);

            $data = require $manifestPath;
            if (!is_array($data)) {
                $issues[] = new ValidationIssue(
                    'FABRYQ.MANIFEST.INVALID',
                    'Manifest must return an array.',
                    $manifestPath
                );
                continue;
            }

            try {
                $manifest = Manifest::fromArray($data);
            } catch (InvalidManifestException $exception) {
                $issues[] = new ValidationIssue(
                    'FABRYQ.MANIFEST.INVALID',
                    $exception->getMessage(),
                    $manifestPath
                );
                continue;
            }

            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $manifest->appId)) {
                $issues[] = new ValidationIssue(
                    'FABRYQ.APP_ID.INVALID',
                    sprintf('Invalid appId "%s".', $manifest->appId),
                    $manifestPath
                );
            }

            if ($manifest->mountpoint !== null) {
                $mountpoint = $manifest->mountpoint;
                $valid = str_starts_with($mountpoint, '/')
                    && ($mountpoint === '/' || !str_ends_with($mountpoint, '/'));

                if (!$valid) {
                    $issues[] = new ValidationIssue(
                        'FABRYQ.MOUNTPOINT.INVALID',
                        sprintf('Invalid mountpoint "%s".', $mountpoint),
                        $manifestPath
                    );
                }

                $mountpoints[$mountpoint][] = $manifestPath;
            }

            $apps[] = new DiscoveredManifest($manifest, $appPath, $manifestPath, $appFolder);
        }

        foreach ($mountpoints as $mountpoint => $paths) {
            if (count($paths) <= 1) {
                continue;
            }

            foreach ($paths as $path) {
                $issues[] = new ValidationIssue(
                    'FABRYQ.MOUNTPOINT.COLLISION',
                    sprintf('Mountpoint "%s" is used by multiple apps.', $mountpoint),
                    $path
                );
            }
        }

        return ['apps' => $apps, 'issues' => $issues];
    }
}
