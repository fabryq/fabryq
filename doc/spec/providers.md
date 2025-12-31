# Providers

## Purpose
Define runtime provider metadata, tagging, priorities, and winner resolution.

## Inputs
- #[FabryqProvider(capability, contract, priority)] on provider classes
- Container tags: fabryq.capability_provider

## Outputs
- Service alias: contract -> winner service id
- Diagnostic map: parameter fabryq.capabilities.map

## Flags
- N/A

## Examples
```php
#[FabryqProvider(
    capability: 'fabryq.bridge.billing.invoice-processor',
    contract: App\Components\BridgeBilling\Contract\InvoiceProcessorInterface::class,
    priority: 0
)]
final class InvoiceProcessorAdapter implements InvoiceProcessorInterface
{
}
```

## Exit Codes
- N/A

## Failure Cases
- capability not matching fabryq.bridge.*
- contract is not an interface
- provider does not implement contract
