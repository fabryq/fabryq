# Context

## Purpose
Define the FabryqContext as a minimal, non-container utility holder.

## Inputs
- LoggerInterface
- ClockInterface
- UlidFactoryInterface

## Outputs
- Injectable FabryqContext service

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq component:create Demo Core
```
Use FabryqContext as an explicit constructor dependency in services or controllers.

## Exit Codes
- N/A

## Failure Cases
- Attempting to use FabryqContext as a service locator (no get() allowed)
- Missing autowire bindings for logger, clock, or ULID factory
