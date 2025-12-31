<?php

/**
 * Service context usage tests.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use DateTimeImmutable;
use Fabryq\Tests\Fixtures\Runtime\TestService;
use Fabryq\Tests\Support\TestContextFactory;
use PHPUnit\Framework\TestCase;

final class ServiceContextTest extends TestCase
{
    public function testServiceUsesContextUtilities(): void
    {
        $now = new DateTimeImmutable('2024-03-01T08:00:00+00:00');
        $payload = TestContextFactory::create($now, '01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $service = new TestService($payload['context']);

        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $service->generateId());
        $this->assertSame($now, $service->now());

        $service->log('service');
        $this->assertSame(1, $payload['logger']->count());
    }

    public function testServiceLogsMultipleMessages(): void
    {
        $payload = TestContextFactory::create();
        $service = new TestService($payload['context']);

        $service->log('first');
        $service->log('second');

        $this->assertSame(2, $payload['logger']->count());
    }

    public function testServiceGeneratedIdIsUlidBase32(): void
    {
        $payload = TestContextFactory::create(null, '01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $service = new TestService($payload['context']);

        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $service->generateId());
    }
}
