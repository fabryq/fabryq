<?php

declare(strict_types=1);

namespace App\Test\Service\Bridge;

use Fabryq\Runtime\Attribute\FabryqProvider;
use App\Components\BridgeTest\Contract\SampleInterface;
use App\Test\HelloWorld\Entity\Sample;

#[FabryqProvider(capability: 'fabryq.bridge.test.sample', contract: SampleInterface::class, priority: 0)]
final class SampleInterfaceAdapter implements SampleInterface
{
    public function __construct(private readonly Sample $provider)
    {
    }

}
