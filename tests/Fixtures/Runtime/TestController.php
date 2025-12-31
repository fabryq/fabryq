<?php

/**
 * Controller fixture for base-layer tests.
 *
 * @package   Fabryq\Tests\Fixtures\Runtime
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Fixtures\Runtime;

use DateTimeImmutable;
use Fabryq\Runtime\Context\FabryqContext;
use Fabryq\Runtime\Controller\AbstractFabryqController;

/**
 * Simple controller exposing the injected context.
 */
final class TestController extends AbstractFabryqController
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
