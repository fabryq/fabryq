<?php

declare(strict_types=1);

/**
 * Lint all PHP files in the repository without external tool dependencies.
 *
 * This script walks the repository tree, excludes vendor/cache artifacts,
 * and runs `php -l` per file. Exit code is non-zero on any lint failure.
 */

$root = dirname(__DIR__);
$excludeDirs = [
    '.git',
    'vendor',
    'var',
    'state',
];

$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo instanceof SplFileInfo) {
        continue;
    }
    if (!$fileInfo->isFile()) {
        continue;
    }

    if ($fileInfo->getExtension() !== 'php') {
        continue;
    }

    $path = $fileInfo->getPathname();
    $relative = substr($path, strlen($root) + 1);
    $segments = explode(DIRECTORY_SEPARATOR, $relative);
    $skip = false;
    foreach ($segments as $segment) {
        if (in_array($segment, $excludeDirs, true)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }

    $files[] = $path;
}

sort($files);

if ($files === []) {
    fwrite(STDERR, "No PHP files found to lint.\n");
    exit(1);
}

$exitCode = 0;
foreach ($files as $path) {
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path);
    passthru($cmd, $status);
    if ($status !== 0) {
        $exitCode = 1;
    }
}

exit($exitCode);
