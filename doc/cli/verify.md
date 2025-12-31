# verify

## Purpose
Run verification gates and write report artifacts.

## Inputs
- Project source tree and manifests

## Outputs
- state/reports/verify/latest.json
- state/reports/verify/latest.md (optional)

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq verify
```

## Exit Codes
- 0: no blockers
- 1: blockers present

## Failure Cases
- Invalid manifests
- Cross-app references
- Asset collisions
- Provider issues
