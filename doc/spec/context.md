# FabryqContext

## Purpose
Provide a minimal, stable set of shared utilities without acting as a container.

## Inputs
- LoggerInterface
- ClockInterface
- UlidFactoryInterface

## Outputs
- FabryqContext service (autowireable)

## Behavior
- Provides logger, clock, and ULID factory only
- No service lookup or get() method
- Use constructor injection when needed

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
- Using FabryqContext as a service locator (forbidden)
- Adding dynamic get()/lookup methods
