<?php

/**
 * App create validation tests.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Tests\Support\FixtureProject;
use PHPUnit\Framework\TestCase;

final class AppCreateValidationTest extends TestCase
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

    /**
     * @dataProvider provideValidNames
     */
    public function testAppCreateAcceptsValidNames(string $name): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', $name, '--dry-run']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideValidNames(): iterable
    {
        yield 'simple' => ['Foo'];
        yield 'with digit' => ['Foo1'];
        yield 'pascal' => ['FooBar'];
    }

    /**
     * @dataProvider provideInvalidNames
     */
    public function testAppCreateRejectsInvalidNames(string $name): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', $name]);
        $this->assertSame(CliExitCode::USER_ERROR, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('Invalid app name', $result['output']);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideInvalidNames(): iterable
    {
        yield 'dot' => ['.'];
        yield 'dot dot' => ['..'];
        yield 'double dot' => ['Foo..Bar'];
        yield 'space' => ['Foo Bar'];
        yield 'lowercase' => ['foo'];
        yield 'slash' => ['Foo/Bar'];
        yield 'backslash' => ['Foo\\Bar'];
        yield 'parent path' => ['../Foo'];
        yield 'current path' => ['./Foo'];
    }
}
