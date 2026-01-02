# Base Layers

## Purpose
Describe the optional base classes that provide FabryqContext access.

## Inputs
- FabryqContext

## Outputs
- AbstractFabryqController
- AbstractFabryqCommand
- AbstractFabryqUseCase

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq component:create Demo Operations
```
Extend the base classes in controllers, commands, or use cases when you want standardized context access.

## Exit Codes
- N/A

## Failure Cases
- Base layer used as a service locator (not allowed)
- Base layer used without FabryqContext being available for autowire
