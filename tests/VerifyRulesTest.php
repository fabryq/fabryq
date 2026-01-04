<?php

/**
 * Verification rule tests.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Report\Severity;
use Fabryq\Tests\Support\FixtureProject;
use PHPUnit\Framework\TestCase;

final class VerifyRulesTest extends TestCase
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

    public function testServiceLocatorForbidden(): void
    {
        $this->bootstrapApp();

        $serviceDir = $this->ensureServiceDir();
        $path = $serviceDir . '/BadService.php';
        $code = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Payments\Service;

use Psr\Container\ContainerInterface;

final class BadService
{
    public function __construct(private ContainerInterface $container)
    {
    }
}
PHP;
        file_put_contents($path, $code);

        $result = FixtureProject::runFabryq($this->projectDir, ['verify']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);

        $report = FixtureProject::readVerifyReport($this->projectDir);
        $this->assertSame('0.4', $report['header']['report_schema_version'] ?? null);
        $this->assertHasFinding($report, 'FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN', Severity::BLOCKER);
        $counts = $this->countFindings($report, 'FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN');
        $this->assertSame(1, $counts[Severity::BLOCKER]);
    }

    public function testServiceLocatorMethodCallForbidden(): void
    {
        $this->bootstrapApp();

        $serviceDir = $this->ensureServiceDir();
        $path = $serviceDir . '/BadCaller.php';
        $code = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Payments\Service;

final class BadCaller
{
    private object $container;

    public function run(): void
    {
        $this->container->get('foo');
    }
}
PHP;
        file_put_contents($path, $code);

        $result = FixtureProject::runFabryq($this->projectDir, ['verify']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);

        $report = FixtureProject::readVerifyReport($this->projectDir);
        $this->assertHasFinding($report, 'FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN', Severity::BLOCKER);
    }

    public function testServiceLocatorStaticCallForbidden(): void
    {
        $this->bootstrapApp();

        $serviceDir = $this->ensureServiceDir();
        $path = $serviceDir . '/BadStatic.php';
        $code = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Payments\Service;

final class BadStatic
{
    public function run(): void
    {
        static::getContainer();
    }
}
PHP;
        file_put_contents($path, $code);

        $result = FixtureProject::runFabryq($this->projectDir, ['verify']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);

        $report = FixtureProject::readVerifyReport($this->projectDir);
        $this->assertHasFinding($report, 'FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN', Severity::BLOCKER);
    }

    public function testEntityBaseRequired(): void
    {
        $this->bootstrapApp();

        $entityDir = $this->projectDir . '/src/Apps/Billing/Payments/Entity';
        if (!is_dir($entityDir)) {
            mkdir($entityDir, 0775, true);
        }

        $badEntity = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Payments\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class BadEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
PHP;

        $traitEntity = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Payments\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fabryq\Runtime\Entity\FabryqEntityInterface;
use Fabryq\Runtime\Entity\FabryqEntityTrait;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
final class TraitEntity implements FabryqEntityInterface
{
    use FabryqEntityTrait;
}
PHP;

        $goodEntity = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Payments\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fabryq\Runtime\Entity\AbstractFabryqEntity;

#[ORM\Entity]
final class GoodEntity extends AbstractFabryqEntity
{
}
PHP;

        file_put_contents($entityDir . '/BadEntity.php', $badEntity);
        file_put_contents($entityDir . '/TraitEntity.php', $traitEntity);
        file_put_contents($entityDir . '/GoodEntity.php', $goodEntity);

        $result = FixtureProject::runFabryq($this->projectDir, ['verify']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);

        $report = FixtureProject::readVerifyReport($this->projectDir);
        $this->assertHasFinding($report, 'FABRYQ.ENTITY.BASE_REQUIRED', Severity::BLOCKER);
        $this->assertHasFinding($report, 'FABRYQ.ENTITY.BASE_REQUIRED', Severity::WARNING);
        $counts = $this->countFindings($report, 'FABRYQ.ENTITY.BASE_REQUIRED');
        $this->assertSame(1, $counts[Severity::BLOCKER]);
        $this->assertSame(1, $counts[Severity::WARNING]);
    }

    /**
     * Bootstrap a minimal app + component via CLI.
     */
    private function bootstrapApp(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', 'Billing', '--mount=/billing']);
        $this->assertSame(0, $result['exitCode'], $result['output']);

        $result = FixtureProject::runFabryq($this->projectDir, ['component:create', 'Billing', 'Payments']);
        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    /**
     * Ensure the service directory exists for fixture writes.
     *
     * @return string
     */
    private function ensureServiceDir(): string
    {
        $serviceDir = $this->projectDir . '/src/Apps/Billing/Payments/Service';
        if (!is_dir($serviceDir)) {
            mkdir($serviceDir, 0775, true);
        }

        return $serviceDir;
    }

    /**
     * Assert the report contains a finding.
     *
     * @param array<string, mixed> $report Report payload.
     * @param string               $ruleKey Rule key.
     * @param string               $severity Severity.
     */
    private function assertHasFinding(array $report, string $ruleKey, string $severity): void
    {
        $findings = $report['findings'] ?? [];
        foreach ($findings as $finding) {
            if (($finding['ruleKey'] ?? '') === $ruleKey && ($finding['severity'] ?? '') === $severity) {
                $this->assertMatchesRegularExpression('/^F-[0-9A-HJKMNP-TV-Z]{8}$/', (string)$finding['id']);
                return;
            }
        }

        $this->fail(sprintf('Expected finding %s (%s) not found.', $ruleKey, $severity));
    }

    /**
     * Count findings by severity for a rule.
     *
     * @param array<string, mixed> $report Report payload.
     * @param string               $ruleKey Rule key.
     *
     * @return array{BLOCKER:int, WARNING:int}
     */
    private function countFindings(array $report, string $ruleKey): array
    {
        $counts = [Severity::BLOCKER => 0, Severity::WARNING => 0];
        $findings = $report['findings'] ?? [];
        foreach ($findings as $finding) {
            if (($finding['ruleKey'] ?? '') !== $ruleKey) {
                continue;
            }
            $severity = $finding['severity'] ?? '';
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            }
        }

        return $counts;
    }
}
