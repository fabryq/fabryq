<?php

declare(strict_types=1);

namespace App\Components\Reporter\Service;

use App\Billing\Checkout\Service\CheckoutService;

final class ReportService
{
    public function __construct(private CheckoutService $checkoutService)
    {
    }
}
