<?php

declare(strict_types=1);

return [
    'appId' => 'test',
    'name' => 'Test',
    'mountpoint' => '/test',
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
            'capabilityId' => 'fabryq.bridge.test.sample',
            'contract' => 'App\\Components\\BridgeTest\\Contract\\SampleInterface',
        ],
    ],
];
