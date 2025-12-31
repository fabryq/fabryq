# Bridges

## Purpose
Describe bridge layout, contracts, DTOs, and NoOp providers created for app crossings.

## Inputs
- Provider app (AppPascal + appId)
- Provider class FQCN
- Consumer usage (method calls, type hints, new)

## Outputs
- Bridge root:
  - src/Components/Bridge<ProviderAppPascal>/.fabryq-bridge
  - Contract/ (<ContractName>Interface.php)
  - Dto/ (<Name>Dto.php when DTO-safe)
  - NoOp/ (<ContractName>NoOp.php)
- Adapter:
  - src/Apps/<ProviderAppPascal>/Service/Bridge/<ContractName>Adapter.php

## Flags
- N/A

## Examples
```text
src/Components/BridgeBilling/Contract/InvoiceProcessorInterface.php
src/Components/BridgeBilling/NoOp/InvoiceProcessorInterfaceNoOp.php
src/Apps/Billing/Service/Bridge/InvoiceProcessorInterfaceAdapter.php
```

## Exit Codes
- N/A

## Failure Cases
- Bridge path exists without .fabryq-bridge marker
- Contract/adapter/no-op file exists with incompatible content
- DTO not safe (Doctrine annotations, non-public properties, unsupported types)
