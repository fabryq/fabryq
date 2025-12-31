# FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN

## Purpose
Block service locator/container usage in app and component code.

## Inputs
- Project code under src/Apps/** and src/Components/** (including bridges)

## Outputs
- Verification findings with ruleKey FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN
- details.primary formats:
  - typehint|<fqcn>
  - method-call|get
  - static-call|getContainer

## Flags
- N/A

## Forbidden Patterns
Type hints or injections of:
- Psr\Container\ContainerInterface
- Symfony\Component\DependencyInjection\ContainerInterface
- Symfony\Contracts\Service\ServiceProviderInterface
- Symfony\Component\DependencyInjection\ServiceLocator

Calls:
- $container->get(...)
- $this->container->get(...)
- static::getContainer()

## Exceptions
- Fabryq runtime internals only (fabryq/runtime). App and component code has no exceptions.

## Examples
```bash
vendor/bin/fabryq verify
```

## Exit Codes
- N/A

## Failure Cases
- Any forbidden pattern in app or component code
