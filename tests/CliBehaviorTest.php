<?php

/**
 * CLI behavior tests for exit codes, locking, and dry-run.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Tests\Support\FixtureProject;
use PHPUnit\Framework\TestCase;

final class CliBehaviorTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = FixtureProject::create();
    }

    protected function tearDown(): void
    {
        FixtureProject::cleanup($this->projectDir);
    }

    public function testAppCreateDryRunDoesNotWrite(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', 'Billing', '--dry-run']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
        $this->assertDirectoryDoesNotExist($this->projectDir . '/src/Apps/Billing');
    }

    public function testAppCreateInvalidNameReturnsUserError(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', 'Bad/Name']);
        $this->assertSame(CliExitCode::USER_ERROR, $result['exitCode'], $result['output']);
    }

    public function testWriteLockBlocksConcurrentWrites(): void
    {
        $lockDir = $this->projectDir . '/var/lock';
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0775, true);
        }

        $handle = fopen($lockDir . '/fabryq.lock', 'c+');
        $this->assertIsResource($handle);

        try {
            $locked = flock($handle, LOCK_EX | LOCK_NB);
            $this->assertTrue($locked, 'Failed to acquire test lock.');

            $result = FixtureProject::runFabryq($this->projectDir, ['app:create', 'LockedApp']);
            $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function testAssetsInstallDryRunDoesNotWrite(): void
    {
        $publicTarget = $this->projectDir . '/public/fabryq';
        $this->assertDirectoryDoesNotExist($publicTarget);

        $result = FixtureProject::runFabryq($this->projectDir, ['assets:install', '--dry-run']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
        $this->assertDirectoryDoesNotExist($publicTarget);
    }

    public function testFixRequiresExplicitMode(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['fix']);
        $this->assertSame(CliExitCode::USER_ERROR, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('Specify exactly one of --dry-run or --apply.', $result['output']);
    }

    public function testFixRejectsMultipleSelectionFlags(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, [
            'fix',
            '--dry-run',
            '--file',
            'src/Apps/Billing/manifest.php',
            '--symbol',
            'App\\Billing\\manifest',
        ]);
        $this->assertSame(CliExitCode::USER_ERROR, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('Use only one selection flag', $result['output']);
    }

    public function testFixRejectsUnknownFindingSelection(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, [
            'fix',
            '--dry-run',
            '--finding',
            'UNKNOWN-ID',
        ]);
        $this->assertSame(CliExitCode::USER_ERROR, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('Finding selection did not resolve', $result['output']);
    }

    public function testDebugFlagShowsExceptionDetails(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:remove', 'MissingApp', '--debug']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('ProjectStateError', $result['output']);
    }
}
