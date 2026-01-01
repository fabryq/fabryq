<?php

declare(strict_types=1);

namespace App\Inventory\Service\Bridge;

use Fabryq\Runtime\Attribute\FabryqProvider;
use App\Components\BridgeInventory\Contract\StockServiceInterface;
use App\Inventory\Warehouse\Service\StockService;

#[FabryqProvider(capability: 'fabryq.bridge.inventory.stock-service', contract: StockServiceInterface::class, priority: 0)]
final class StockServiceInterfaceAdapter implements StockServiceInterface
{
    public function __construct(private readonly StockService $provider)
    {
    }

}
