# Base Layers

## Purpose
Provide optional base classes that expose FabryqContext in common entrypoints.

## Inputs
- FabryqContext

## Outputs
- AbstractFabryqController
- AbstractFabryqCommand
- AbstractFabryqUseCase

## Flags
- N/A

## Examples
```php
use Fabryq\Runtime\Controller\AbstractFabryqController;

final class InvoiceController extends AbstractFabryqController
{
    public function index(): Response
    {
        $this->ctx->logger->info('index');
        // ...
    }
}
```

```php
use Fabryq\Runtime\Command\AbstractFabryqCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:demo')]
final class DemoCommand extends AbstractFabryqCommand
{
    // ...
}
```

## Exit Codes
- N/A

## Failure Cases
- Using base classes to access the container directly
- Hiding constructor dependencies in the context
