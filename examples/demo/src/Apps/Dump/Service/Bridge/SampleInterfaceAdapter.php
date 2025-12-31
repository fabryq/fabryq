<?php

declare(strict_types=1);

namespace App\Dump\Service\Bridge;

use Fabryq\Runtime\Attribute\FabryqProvider;
use App\Components\BridgeDump\Contract\SampleInterface;
use App\Dump\HelloWorld\Entity\Sample;

#[FabryqProvider(capability: 'fabryq.bridge.dump.sample', contract: SampleInterface::class, priority: 0)]
final class SampleInterfaceAdapter implements SampleInterface
{
    public function __construct(private readonly Sample $provider)
    {
    }

    public function getId(): string
    {
        return $this->provider->getId();
    }

}
