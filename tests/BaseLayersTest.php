<?php

/**
 * Base-layer context injection tests.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Tests\Fixtures\Runtime\TestCommand;
use Fabryq\Tests\Fixtures\Runtime\TestController;
use Fabryq\Tests\Fixtures\Runtime\TestUseCase;
use Fabryq\Tests\Support\TestContextFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BaseLayersTest extends TestCase
{
    public function testControllerBaseInjectsContext(): void
    {
        $payload = TestContextFactory::create();
        $controller = new TestController($payload['context']);

        $this->assertSame($payload['context'], $controller->context());
    }

    public function testControllerUsesContextUtilities(): void
    {
        $now = new DateTimeImmutable('2024-05-01T12:00:00+00:00');
        $payload = TestContextFactory::create($now, '01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $controller = new TestController($payload['context']);

        $this->assertSame($now, $controller->now());
        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $controller->generateId());

        $controller->log('controller');
        $this->assertSame(1, $payload['logger']->count());
    }

    public function testCommandBaseInjectsContext(): void
    {
        $payload = TestContextFactory::create();
        $command = new TestCommand($payload['context']);

        $this->assertSame($payload['context'], $command->context());
    }

    public function testCommandUsesContextUtilities(): void
    {
        $now = new DateTimeImmutable('2024-06-01T09:15:00+00:00');
        $payload = TestContextFactory::create($now, '01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $command = new TestCommand($payload['context']);

        $this->assertSame($now, $command->now());
        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $command->generateId());

        $command->log('command');
        $this->assertSame(1, $payload['logger']->count());
    }

    public function testUseCaseBaseInjectsContext(): void
    {
        $payload = TestContextFactory::create();
        $useCase = new TestUseCase($payload['context']);

        $this->assertSame($payload['context'], $useCase->context());
    }

    public function testUseCaseUsesContextUtilities(): void
    {
        $now = new DateTimeImmutable('2024-07-01T18:30:00+00:00');
        $payload = TestContextFactory::create($now, '01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $useCase = new TestUseCase($payload['context']);

        $this->assertSame($now, $useCase->now());
        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $useCase->generateId());

        $useCase->log('usecase');
        $this->assertSame(1, $payload['logger']->count());
    }
}
