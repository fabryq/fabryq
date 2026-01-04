<?php

/**
 * Tests for report location link rendering.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Config\ProjectConfig;
use Fabryq\Cli\Report\ReportLinkBuilder;
use PHPUnit\Framework\TestCase;

final class ReportLinkBuilderTest extends TestCase
{
    public function testPhpStormLinks(): void
    {
        [$projectDir, $builder] = $this->makeBuilder('phpstorm');

        $path = 'src/Apps/Foo/Bar.php';
        $formatted = $builder->format($path, 12);

        $this->assertStringContainsString($path . ':12', $formatted);
        $this->assertStringContainsString('phpstorm://open?file=', $formatted);
        $this->assertStringContainsString('&line=12', $formatted);

        $this->cleanup($projectDir);
    }

    public function testFileLinks(): void
    {
        [$projectDir, $builder] = $this->makeBuilder('file');

        $path = 'src/Apps/Foo/Bar.php';
        $formatted = $builder->format($path, 7);

        $this->assertStringContainsString($path . ':7', $formatted);
        $this->assertStringContainsString('file://', $formatted);

        $this->cleanup($projectDir);
    }

    public function testNoLinks(): void
    {
        [$projectDir, $builder] = $this->makeBuilder('none');

        $path = 'src/Apps/Foo/Bar.php';
        $formatted = $builder->format($path, 3);

        $this->assertSame($path . ':3', $formatted);

        $this->cleanup($projectDir);
    }

    /**
     * @param string $scheme
     *
     * @return array{0:string,1:ReportLinkBuilder}
     */
    private function makeBuilder(string $scheme): array
    {
        $projectDir = sys_get_temp_dir() . '/fabryq-report-' . bin2hex(random_bytes(3));
        mkdir($projectDir . '/src/Apps/Foo', 0775, true);
        file_put_contents($projectDir . '/src/Apps/Foo/Bar.php', "<?php\n");

        $yaml = <<<YAML
reports:
  links:
    enabled: true
    scheme: '{$scheme}'
YAML;
        file_put_contents($projectDir . '/fabryq.yaml', $yaml);

        $config = new ProjectConfig($projectDir);
        $builder = new ReportLinkBuilder($config, $projectDir);

        return [$projectDir, $builder];
    }

    private function cleanup(string $projectDir): void
    {
        if (!is_dir($projectDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
            } else {
                unlink($fileInfo->getPathname());
            }
        }

        rmdir($projectDir);
    }
}
