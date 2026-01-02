<?php

declare (strict_types=1);

namespace App\Billing\Checkout\Service;

use App\Components\BridgeInventory\Contract\StockServiceInterface;
final class CheckoutService
{
    public function __construct(private \App\Inventory\Warehouse\Service\StockService $stockService)
    {
    }
}
