# Fix Logging

## Purpose
Define fix-run log artifacts and required fields.

## Inputs
- Fixer key (assets, crossing)
- Mode (dry-run, apply)
- Plan data and change list

## Outputs
- state/fix/<runId>/plan.md
- state/fix/<runId>/changes.json
- state/fix/latest.json
- state/fix/latest.md

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq fix assets --dry-run --all
```

## Exit Codes
- N/A

## Failure Cases
- Existing plan differs from current plan
- Blockers prevent apply
