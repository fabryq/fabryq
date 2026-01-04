<?php

/**
 * Tests for project-level Fabryq configuration defaults and overrides.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Config\ProjectConfig;
use Fabryq\Cli\Error\ProjectStateError;
use PHPUnit\Framework\TestCase;

final class ProjectConfigTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $projects = [];

    protected function tearDown(): void
    {
        foreach ($this->projects as $projectDir) {
            $this->cleanup($projectDir);
        }
        $this->projects = [];
    }

    public function testDefaultsAreAppliedWhenConfigMissing(): void
    {
        $projectDir = $this->makeProjectDir();
        $config = new ProjectConfig($projectDir);

        $controller = $config->controller();
        $this->assertSame('', $controller['route_prefix']);
        $this->assertSame('', $controller['route_name_prefix']);
        $this->assertSame('json', $controller['default_format']);
        $this->assertFalse($controller['security']['enabled']);
        $this->assertSame('', $controller['security']['attribute']);
        $this->assertFalse($controller['templates']['enabled']);
        $this->assertSame('', $controller['templates']['namespace']);
        $this->assertFalse($controller['translations']['enabled']);
        $this->assertSame('messages', $controller['translations']['domain']);

        $reports = $config->reports();
        $this->assertTrue($reports['links']['enabled']);
        $this->assertSame('phpstorm', $reports['links']['scheme']);
    }

    public function testOverridesMergeConfig(): void
    {
        $yaml = <<<YAML
controller:
  route_prefix: '/api'
  security:
    enabled: true
    attribute: 'ROLE_ADMIN'
reports:
  links:
    enabled: false
    scheme: 'file'
YAML;
        $projectDir = $this->makeProjectDir($yaml);
        $config = new ProjectConfig($projectDir);

        $controller = $config->controller();
        $this->assertSame('/api', $controller['route_prefix']);
        $this->assertSame('json', $controller['default_format']);
        $this->assertTrue($controller['security']['enabled']);
        $this->assertSame('ROLE_ADMIN', $controller['security']['attribute']);
        $this->assertSame('messages', $controller['translations']['domain']);

        $reports = $config->reports();
        $this->assertFalse($reports['links']['enabled']);
        $this->assertSame('file', $reports['links']['scheme']);
    }

    public function testInvalidYamlThrowsProjectStateError(): void
    {
        $projectDir = $this->makeProjectDir('controller: [');

        $this->expectException(ProjectStateError::class);
        $this->expectExceptionMessage('fabryq.yaml is invalid');

        new ProjectConfig($projectDir);
    }

    public function testNonMappingThrowsProjectStateError(): void
    {
        $projectDir = $this->makeProjectDir('- foo');

        $this->expectException(ProjectStateError::class);
        $this->expectExceptionMessage('fabryq.yaml must decode to a mapping.');

        new ProjectConfig($projectDir);
    }

    private function makeProjectDir(?string $yaml = null): string
    {
        $projectDir = sys_get_temp_dir() . '/fabryq-config-' . bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);

        if ($yaml !== null) {
            file_put_contents($projectDir . '/fabryq.yaml', $yaml);
        }

        $this->projects[] = $projectDir;

        return $projectDir;
    }

    private function cleanup(string $projectDir): void
    {
        $configPath = $projectDir . '/fabryq.yaml';
        if (is_file($configPath)) {
            unlink($configPath);
        }

        if (is_dir($projectDir)) {
            rmdir($projectDir);
        }
    }
}
