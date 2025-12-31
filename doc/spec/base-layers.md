# Base Layers

## Purpose
Provide optional base classes that expose FabryqContext in common entrypoints.

## Inputs
- FabryqContext

## Outputs
- AbstractFabryqController
- AbstractFabryqCommand
- AbstractFabryqUseCase

## Behavior
- Base classes expose FabryqContext as $ctx
- Use cases are optional; controllers/commands may extend base or inject context directly

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq app:create billing --mount=/billing
vendor/bin/fabryq component:create billing Invoices
```

## Exit Codes
- N/A

## Failure Cases
- Using base classes to access the container directly
- Hiding constructor dependencies in the context
