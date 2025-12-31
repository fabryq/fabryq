# fix

## Purpose
Dispatch fixers based on autofixable findings.

## Inputs
- Verification findings
- Selection flags

## Outputs
- Delegates to fix:assets or fix:crossing

## Flags
- --dry-run
- --apply
- --all
- --file=<path>
- --symbol=<name>
- --finding=<id>

## Examples
```bash
vendor/bin/fabryq fix --dry-run --all
vendor/bin/fabryq fix --apply --finding=F-1A2B3C4D
```

## Exit Codes
- 0: no matching fixable findings or successful run
- 1: invalid selection or fixer failure

## Failure Cases
- Selection does not resolve to autofixable findings
- Fixer-specific blockers
