<?php

/**
 * Tests entity-to-interface replacement in fix:crossing.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Tests\Support\FixtureProject;
use PHPUnit\Framework\TestCase;

final class CrossingEntityToInterfaceTest extends TestCase
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

    public function testFixCrossingReplacesEntityTypesWithInterfaces(): void
    {
        $this->bootstrapApps();

        $providerEntityDir = $this->projectDir . '/src/Apps/Inventory/Stock/Entity';
        if (!is_dir($providerEntityDir)) {
            mkdir($providerEntityDir, 0775, true);
        }

        $entity = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Inventory\Stock\Entity;

final class User
{
}
PHP;
        file_put_contents($providerEntityDir . '/User.php', $entity);

        $consumerServiceDir = $this->projectDir . '/src/Apps/Billing/Payments/Service';
        if (!is_dir($consumerServiceDir)) {
            mkdir($consumerServiceDir, 0775, true);
        }

        $consumerCode = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Payments\Service;

use App\Inventory\Stock\Entity\User;

final class OrderService
{
    /** @var User */
    private User $user;

    /**
     * @param User $user
     *
     * @return User
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param User $user
     *
     * @return User
     */
    public function handle(User $user): User
    {
        return $user;
    }
}
PHP;
        $consumerPath = $consumerServiceDir . '/OrderService.php';
        file_put_contents($consumerPath, $consumerCode);

        $result = FixtureProject::runFabryq($this->projectDir, ['fix', 'crossing', '--apply']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);

        $updated = file_get_contents($consumerPath);
        if ($updated === false) {
            self::fail('Unable to read updated consumer file.');
        }
        $this->assertStringNotContainsString('App\\Inventory\\Stock\\Entity\\User', $updated);
        $this->assertStringContainsString('App\\Inventory\\Stock\\Contracts\\UserInterface', $updated);

        $interfacePath = $this->projectDir . '/src/Apps/Inventory/Stock/Contracts/UserInterface.php';
        $this->assertFileExists($interfacePath);
    }

    private function bootstrapApps(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', 'Billing']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);

        $result = FixtureProject::runFabryq($this->projectDir, ['component:create', 'Billing', 'Payments']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);

        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', 'Inventory']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);

        $result = FixtureProject::runFabryq($this->projectDir, ['component:create', 'Inventory', 'Stock']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);
    }
}
