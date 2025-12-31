# FabryqContext

## Purpose
Provide a minimal, stable set of shared utilities without acting as a container.

## Inputs
- LoggerInterface
- ClockInterface
- UlidFactoryInterface

## Outputs
- FabryqContext service (autowireable)

## Flags
- N/A

## Examples
```php
use Fabryq\Runtime\Context\FabryqContext;

final class InvoiceService
{
    public function __construct(private readonly FabryqContext $ctx)
    {
    }

    public function handle(): void
    {
        $this->ctx->logger->info('start');
        $now = $this->ctx->clock->now();
        $id = $this->ctx->ulids->create()->toRfc4122();
    }
}
```

## Exit Codes
- N/A

## Failure Cases
- Using FabryqContext as a service locator (forbidden)
- Adding dynamic get()/lookup methods
