# Wrapper

## Purpose
Provide a stable entrypoint via vendor/bin/fabryq with alias mapping and root detection.

## Inputs
- vendor/bin/fabryq <alias> [args...]

## Outputs
- Executes bin/console fabryq:* commands from project root

## Flags
- --help (shows aliases and examples)

## Examples
```bash
vendor/bin/fabryq verify
vendor/bin/fabryq app:create Billing --mount=/billing
vendor/bin/fabryq fix crossing --dry-run
```

## Exit Codes
- 0: success
- 1: unknown command
- 2: project root not found

## Failure Cases
- Root detection fails (missing composer.json or bin/console)
- Unknown alias
