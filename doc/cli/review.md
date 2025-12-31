# review

## Purpose
Generate a human-readable review report from verification findings.

## Inputs
- Verification findings

## Outputs
- state/reports/review/latest.md

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq review
```

## Exit Codes
- 0: no blockers
- 1: blockers present

## Failure Cases
- Verification blockers
