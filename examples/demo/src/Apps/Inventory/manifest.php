<?php

declare(strict_types=1);

return [
    'appId' => 'inventory',
    'name' => 'Inventory',
    'mountpoint' => '/inventory',
    'consumes' => [
    ],
    'events' => [
        'publishes' => [
        ],
        'subscribes' => [
        ],
    ],
    'provides' => [
        [
            'capabilityId' => 'fabryq.bridge.inventory.stock-service',
            'contract' => 'App\\Components\\BridgeInventory\\Contract\\StockServiceInterface',
        ],
    ],
];
