<?php

/**
 * Helper for building CLI-based fixture projects.
 *
 * @package   Fabryq\Tests\Support
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Support;

use RuntimeException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

/**
 * Creates and manages temporary fixture projects.
 */
final class FixtureProject
{
    /**
     * Remove a fixture directory recursively.
     *
     * @param string $path Fixture path.
     */
    public static function cleanup(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
            } else {
                unlink($fileInfo->getPathname());
            }
        }

        rmdir($path);
    }

    /**
     * Create a new fixture project directory.
     *
     * @return string Absolute fixture path.
     */
    public static function create(): string
    {
        $target = sys_get_temp_dir() . '/fabryq-test-' . bin2hex(random_bytes(4));
        self::copyDir(self::skeletonPath(), $target);
        self::ensureVarDirectory($target);

        // Fix 1: Ensure environment config directories exist to prevent Kernel glob errors
        $envDirs = [
            $target . '/config/packages/test',
            $target . '/config/routes/test',
        ];
        foreach ($envDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }

        // Fix 2: Ensure DATABASE_URL is defined for the test kernel
        $envFile = $target . '/.env';
        if (is_file($envFile)) {
            file_put_contents(
                $envFile,
                "\nDATABASE_URL=\"sqlite:///%kernel.project_dir%/var/data.db\"\n",
                FILE_APPEND
            );
        }

        self::installAutoload($target);

        return $target;
    }

    /**
     * Read the verify report.
     *
     * @param string $projectDir Fixture directory.
     *
     * @return array<string, mixed>
     */
    public static function readVerifyReport(string $projectDir): array
    {
        $path = $projectDir . '/state/reports/verify/latest.json';
        if (!is_file($path)) {
            throw new RuntimeException('Verify report missing at ' . $path . "\nMaybe verifying failed?");
        }

        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            throw new RuntimeException('Verify report is invalid JSON.');
        }

        return $data;
    }

    /**
     * Run a Fabryq console command inside the fixture.
     *
     * @param string   $projectDir Fixture directory.
     * @param string[] $args       CLI arguments.
     *
     * @return array{exitCode:int, output:string}
     */
    public static function runFabryq(string $projectDir, array $args): array
    {
        $aliases = [
            'verify' => 'fabryq:verify',
            'review' => 'fabryq:review',
            'doctor' => 'fabryq:doctor',
            'graph' => 'fabryq:graph',
            'assets:install' => 'fabryq:assets:install',
            'app:create' => 'fabryq:app:create',
            'app:remove' => 'fabryq:app:remove',
            'component:create' => 'fabryq:component:create',
            'component:add:templates' => 'fabryq:component:add:templates',
            'component:add:translations' => 'fabryq:component:add:translations',
            'component:remove' => 'fabryq:component:remove',
            'crud:create' => 'fabryq:crud:create',
            'fix' => 'fabryq:fix',
        ];

        $command = $args[0] ?? '';
        $extraArgs = array_slice($args, 1);

        if ($command === 'fix' && isset($args[1])) {
            if ($args[1] === 'assets') {
                $command = 'fix assets';
                $extraArgs = array_slice($args, 2);
            } elseif ($args[1] === 'crossing') {
                $command = 'fix crossing';
                $extraArgs = array_slice($args, 2);
            }
        }

        if (str_starts_with($command, 'fabryq:')) {
            $mapped = $command;
        } elseif (isset($aliases[$command])) {
            $mapped = $aliases[$command];
        } elseif ($command === 'fix assets') {
            $mapped = 'fabryq:fix:assets';
        } elseif ($command === 'fix crossing') {
            $mapped = 'fabryq:fix:crossing';
        } else {
            throw new RuntimeException(sprintf('Unknown Fabryq command: %s', $command));
        }

        $console = $projectDir . '/bin/console';
        $cmd = array_merge([PHP_BINARY, $console, $mapped], $extraArgs);

        return self::runCommand($cmd, $projectDir);
    }

    /**
     * Copy the skeleton into a new directory.
     *
     * @param string $source Source directory.
     * @param string $target Target directory.
     */
    private static function copyDir(string $source, string $target): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            $destPath = $target . '/' . $iterator->getSubPathName();
            if ($fileInfo->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0775, true);
                }
            } else {
                if (!is_dir(dirname($destPath))) {
                    mkdir(dirname($destPath), 0775, true);
                }
                copy($fileInfo->getPathname(), $destPath);
            }
        }
    }

    /**
     * Install a local vendor/autoload.php into the fixture.
     *
     * @param string $projectDir Fixture directory.
     */
    private static function installAutoload(string $projectDir): void
    {
        $rootAutoload = self::rootPath() . '/vendor/autoload.php';
        if (!is_file($rootAutoload)) {
            throw new RuntimeException('Root vendor/autoload.php is missing. Run composer install.');
        }

        $vendorDir = $projectDir . '/vendor';
        if (!is_dir($vendorDir)) {
            mkdir($vendorDir, 0775, true);
        }

        $autoloadPath = $vendorDir . '/autoload.php';
        $content = sprintf(
            <<<'PHP'
<?php

declare(strict_types=1);

$rootAutoload = %s;
if (!is_file($rootAutoload)) {
    throw new RuntimeException('Root autoload not found: ' . $rootAutoload);
}

require $rootAutoload;

$projectRoot = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($projectRoot): void {
    if ($class === 'App\\Kernel') {
        $path = $projectRoot . '/src/Kernel.php';
        if (is_file($path)) {
            require $path;
        }
        return;
    }

    $prefixes = [
        'App\\Components\\' => $projectRoot . '/src/Components/',
        'App\\' => $projectRoot . '/src/Apps/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $relative = str_replace('\\', '/', $relative);
        $path = $baseDir . $relative . '.php';
        if (is_file($path)) {
            require $path;
        }
        return;
    }
}, true, true);
PHP,
            var_export($rootAutoload, true)
        );

        file_put_contents($autoloadPath, $content);
    }

    /**
     * Path to the repo root.
     *
     * @return string
     */
    private static function rootPath(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Ensure the var directory exists for SQLite and cache files.
     *
     * @param string $projectDir Fixture directory.
     */
    private static function ensureVarDirectory(string $projectDir): void
    {
        $path = $projectDir . '/var';
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    /**
     * Run a command with a working directory.
     *
     * @param array<int, string> $cmd Command argv.
     * @param string             $cwd Working directory.
     *
     * @return array{exitCode:int, output:string}
     */
    private static function runCommand(array $cmd, string $cwd): array
    {
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Pass current env to subprocess but ensure APP_ENV is explicitly set if needed
        $env = $_ENV;
        $env['APP_ENV'] = $_SERVER['APP_ENV'] ?? 'test';
        $env['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? '0';
        $env['DATABASE_URL'] = $env['DATABASE_URL'] ?? sprintf('sqlite:///%s/var/data.db', $cwd);

        $process = proc_open($cmd, $descriptorSpec, $pipes, $cwd, $env);
        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start process.');
        }

        $output = stream_get_contents($pipes[1]) ?: '';
        $output .= stream_get_contents($pipes[2]) ?: '';
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($process);

        return ['exitCode' => $exitCode, 'output' => $output];
    }

    /**
     * Path to the skeleton directory.
     *
     * @return string
     */
    private static function skeletonPath(): string
    {
        return self::rootPath() . '/packages/skeleton';
    }
}
