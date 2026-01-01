<?php

declare(strict_types=1);

return [
    'appId' => 'billing',
    'name' => 'Billing',
    'mountpoint' => '/billing',
    'consumes' => [
        [
            'capabilityId' => 'fabryq.bridge.inventory.stock-service',
            'required' => true,
            'contract' => 'App\\Components\\BridgeInventory\\Contract\\StockServiceInterface',
        ],
    ],
    'events' => [
        'publishes' => [
        ],
        'subscribes' => [
        ],
    ],
];
