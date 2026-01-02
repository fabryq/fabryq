<?php

/**
 * Manifest definition for the Test demo application.
 *
 * @package   App\Test
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

use Fabryq\Contracts\Capability\CapabilityIds;
use Fabryq\Contracts\Capability\HttpClientInterface;

/**
 * Manifest payload consumed by the runtime discovery process.
 *
 * @return array<string, mixed>
 */
return [
    'appId' => 'faults',
    'name' => 'Faults',
    'mountpoint' => '/faults',
    'consumes' => [
    ],
    'events' => [
        'publishes' => [],
        'subscribes' => [],
    ],
];
