<?php

/**
 * Tests for app/component removal commands.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Tests\Support\FixtureProject;
use PHPUnit\Framework\TestCase;

final class RemoveCommandTest extends TestCase
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

    public function testComponentRemoveDryRunDoesNotDelete(): void
    {
        $this->bootstrapApp('Billing', 'Payments');

        $result = FixtureProject::runFabryq($this->projectDir, ['component:remove', 'Payments', '--dry-run']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
        $this->assertDirectoryExists($this->projectDir . '/src/Apps/Billing/Payments');
    }

    public function testComponentRemoveBlockedByReferences(): void
    {
        $this->bootstrapApp('Billing', 'Payments');
        $this->bootstrapComponent('Billing', 'Orders');

        $serviceDir = $this->projectDir . '/src/Apps/Billing/Orders/Service';
        if (!is_dir($serviceDir)) {
            mkdir($serviceDir, 0775, true);
        }

        $code = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Orders\Service;

use App\Billing\Payments\Service\PaymentService;

final class UsesPayments
{
    public function __construct(private PaymentService $service)
    {
    }
}
PHP;
        file_put_contents($serviceDir . '/UsesPayments.php', $code);

        $result = FixtureProject::runFabryq($this->projectDir, ['component:remove', 'Payments']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);
        $this->assertDirectoryExists($this->projectDir . '/src/Apps/Billing/Payments');
    }

    public function testComponentRemoveMissingReturnsProjectStateError(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['component:remove', 'Missing']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);
    }

    public function testAppRemoveDeletesWhenUnreferenced(): void
    {
        $this->bootstrapApp('Inventory', 'Stock');

        $result = FixtureProject::runFabryq($this->projectDir, ['app:remove', 'Inventory']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
        $this->assertDirectoryDoesNotExist($this->projectDir . '/src/Apps/Inventory');
    }

    public function testAppRemoveBlockedByReferences(): void
    {
        $this->bootstrapApp('Inventory', 'Stock');
        $this->bootstrapApp('Billing', 'Orders');

        $serviceDir = $this->projectDir . '/src/Apps/Billing/Orders/Service';
        if (!is_dir($serviceDir)) {
            mkdir($serviceDir, 0775, true);
        }

        $code = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Orders\Service;

use App\Inventory\Stock\Service\StockService;

final class UsesInventory
{
    public function __construct(private StockService $service)
    {
    }
}
PHP;
        file_put_contents($serviceDir . '/UsesInventory.php', $code);

        $result = FixtureProject::runFabryq($this->projectDir, ['app:remove', 'Inventory']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);
        $this->assertDirectoryExists($this->projectDir . '/src/Apps/Inventory');
    }

    public function testAppRemoveMissingAppReturnsProjectStateError(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:remove', 'UnknownApp']);
        $this->assertSame(CliExitCode::PROJECT_STATE_ERROR, $result['exitCode'], $result['output']);
    }

    public function testComponentRemoveRejectsAmbiguousComponent(): void
    {
        $this->bootstrapApp('Billing', 'Payments');
        $this->bootstrapApp('Inventory', 'Payments');

        $result = FixtureProject::runFabryq($this->projectDir, ['component:remove', 'Payments']);
        $this->assertSame(CliExitCode::USER_ERROR, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('ambiguous', $result['output']);
    }

    private function bootstrapApp(string $app, string $component): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', $app]);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);

        $this->bootstrapComponent($app, $component);
    }

    private function bootstrapComponent(string $app, string $component): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['component:create', $app, $component]);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
    }
}
