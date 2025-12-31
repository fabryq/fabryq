<?php

declare(strict_types=1);

namespace App\Components\BridgeTest\NoOp;

use DateTimeImmutable;
use DateTimeInterface;
use Fabryq\Runtime\Attribute\FabryqProvider;
use App\Components\BridgeTest\Contract\SampleInterface;

#[FabryqProvider(capability: 'fabryq.bridge.test.sample', contract: SampleInterface::class, priority: -1000)]
final class SampleInterfaceNoOp implements SampleInterface
{
}
