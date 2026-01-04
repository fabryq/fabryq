<?php

/**
 * Tests for CRUD generator scaffolding.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Tests\Support\FixtureProject;
use PHPUnit\Framework\TestCase;

final class CrudGeneratorTest extends TestCase
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

    public function testCrudCreateUsesControllerDefaults(): void
    {
        $result = FixtureProject::runFabryq($this->projectDir, ['app:create', 'Billing']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);

        $customConfig = <<<YAML
controller:
  route_prefix: '/api'
  route_name_prefix: 'api.'
  default_format: 'json'
  security:
    enabled: true
    attribute: 'ROLE_ADMIN'
  templates:
    enabled: true
    namespace: 'App'
  translations:
    enabled: true
    domain: 'fabryq'
YAML;
        file_put_contents($this->projectDir . '/fabryq.yaml', $customConfig);

        $result = FixtureProject::runFabryq($this->projectDir, ['crud:create', 'Billing', 'Order']);
        $this->assertSame(CliExitCode::SUCCESS, $result['exitCode'], $result['output']);

        $base = $this->projectDir . '/src/Apps/Billing/Order';
        foreach ($this->expectedPaths('Order') as $path) {
            $this->assertFileExists($base . '/' . $path);
        }

        $controller = file_get_contents($base . '/Controller/OrderController.php');
        $this->assertStringContainsString("Route('/api/order'", $controller);
        $this->assertStringContainsString("name: 'api.order.list'", $controller);
        $this->assertStringContainsString("format: 'json'", $controller);
        $this->assertStringContainsString("#[IsGranted('ROLE_ADMIN')]", $controller);
        $this->assertStringContainsString("@App/order/list.html.twig", $controller);
        $this->assertStringContainsString("trans('order.list', [], 'fabryq')", $controller);

        $useCase = file_get_contents($base . '/UseCase/Order/ListOrderUseCase.php');
        $this->assertStringContainsString('TODO: implement list Order use case.', $useCase);
    }

    /**
     * @param string $resource
     *
     * @return array<int, string>
     */
    private function expectedPaths(string $resource): array
    {
        return [
            'Controller/' . $resource . 'Controller.php',
            'UseCase/' . $resource . '/List' . $resource . 'UseCase.php',
            'UseCase/' . $resource . '/Get' . $resource . 'UseCase.php',
            'UseCase/' . $resource . '/Create' . $resource . 'UseCase.php',
            'UseCase/' . $resource . '/Update' . $resource . 'UseCase.php',
            'UseCase/' . $resource . '/Delete' . $resource . 'UseCase.php',
            'Dto/' . $resource . '/List' . $resource . 'Request.php',
            'Dto/' . $resource . '/List' . $resource . 'Response.php',
            'Dto/' . $resource . '/Get' . $resource . 'Request.php',
            'Dto/' . $resource . '/Get' . $resource . 'Response.php',
            'Dto/' . $resource . '/Create' . $resource . 'Request.php',
            'Dto/' . $resource . '/Create' . $resource . 'Response.php',
            'Dto/' . $resource . '/Update' . $resource . 'Request.php',
            'Dto/' . $resource . '/Update' . $resource . 'Response.php',
            'Dto/' . $resource . '/Delete' . $resource . 'Request.php',
            'Dto/' . $resource . '/Delete' . $resource . 'Response.php',
        ];
    }
}
