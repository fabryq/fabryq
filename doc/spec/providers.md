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
```bash
vendor/bin/fabryq verify
vendor/bin/fabryq doctor
```

## Exit Codes
- N/A

## Failure Cases
- capability not matching fabryq.bridge.*
- contract is not an interface
- provider does not implement contract
