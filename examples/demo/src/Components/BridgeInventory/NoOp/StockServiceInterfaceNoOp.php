<?php

declare(strict_types=1);

namespace App\Components\BridgeInventory\NoOp;

use DateTimeImmutable;
use DateTimeInterface;
use Fabryq\Runtime\Attribute\FabryqProvider;
use App\Components\BridgeInventory\Contract\StockServiceInterface;

#[FabryqProvider(capability: 'fabryq.bridge.inventory.stock-service', contract: StockServiceInterface::class, priority: -1000)]
final class StockServiceInterfaceNoOp implements StockServiceInterface
{
}
