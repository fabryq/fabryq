<?php

declare(strict_types=1);

/**
 * Environment gate for Fabryq: PHP 8.2.x, Composer available, sqlite extensions.
 */

$errors = [];

if (version_compare(PHP_VERSION, '8.2.0', '<') || version_compare(PHP_VERSION, '8.5.0', '>=')) {
    $errors[] = sprintf('PHP 8.2.x required, current: %s', PHP_VERSION);
}

$composerOk = true;
$composerOutput = [];
$composerStatus = 0;
@exec('composer --version', $composerOutput, $composerStatus);
if ($composerStatus !== 0) {
    $composerOk = false;
    $errors[] = 'Composer not available on PATH (composer --version failed).';
}

$requiredExtensions = ['pdo_sqlite', 'sqlite3'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $errors[] = sprintf('Missing PHP extension: %s', $ext);
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Environment gate failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "Environment gate passed.\n");
exit(0);
