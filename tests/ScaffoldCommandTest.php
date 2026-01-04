<?php

/**
 * Tests for scaffold add commands.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Tests\Support\FixtureProject;
use PHPUnit\Framework\TestCase;

final class ScaffoldCommandTest extends TestCase
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

    public function testComponentAddTemplatesCreatesScaffold(): void
    {
        $this->bootstrapComponent('Billing', 'Payments');

        $targetDir = $this->projectDir . '/src/Apps/Billing/Payments/Resources/templates';
        $this->assertDirectoryDoesNotExist($targetDir);

        $result = FixtureProject::runFabryq($this->projectDir, ['component:add:templates', 'Payments']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
        $this->assertDirectoryExists($targetDir);
        $this->assertFileExists($targetDir . '/.keep');
    }

    public function testComponentAddTranslationsDryRunDoesNotWrite(): void
    {
        $this->bootstrapComponent('Billing', 'Payments');

        $targetDir = $this->projectDir . '/src/Apps/Billing/Payments/Resources/translations';
        $this->assertDirectoryDoesNotExist($targetDir);

        $result = FixtureProject::runFabryq($this->projectDir, ['component:add:translations', 'Payments', '--dry-run']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
        $this->assertDirectoryDoesNotExist($targetDir);
    }

    public function testComponentAddTranslationsMissingComponentReturnsProjectStateError(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['component:add:translations', 'Missing']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);
    }

    public function testComponentAddTemplatesRejectsAmbiguousComponent(): void
    {
        $this->bootstrapComponent('Billing', 'Payments');
        $this->bootstrapComponent('Inventory', 'Payments');

        $result = FixtureProject::runFabryq($this->projectDir, ['component:add:templates', 'Payments']);
        $this->assertSame(CliExitCode::USER_ERROR, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('ambiguous', $result['output']);
    }

    private function bootstrapComponent(string $app, string $component): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', $app]);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);

        $result = FixtureProject::runFabryq($this->projectDir, ['component:create', $app, $component]);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
    }
}
