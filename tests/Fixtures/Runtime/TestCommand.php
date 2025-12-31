<?php

/**
 * Command fixture for base-layer tests.
 *
 * @package   Fabryq\Tests\Fixtures\Runtime
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Fixtures\Runtime;

use DateTimeImmutable;
use Fabryq\Runtime\Command\AbstractFabryqCommand;
use Fabryq\Runtime\Context\FabryqContext;

/**
 * Simple command exposing the injected context.
 */
final class TestCommand extends AbstractFabryqCommand
{
    public function context(): FabryqContext
    {
        return $this->ctx;
    }

    public function now(): DateTimeImmutable
    {
        return $this->ctx->clock->now();
    }

    public function generateId(): string
    {
        return $this->ctx->ulids->create()->toBase32();
    }

    public function log(string $message): void
    {
        $this->ctx->logger->info($message);
    }
}
