# Finding IDs

## Purpose
Define deterministic finding IDs for reports and fixes.

## Inputs
- ruleKey
- location.file (relative, forward slashes)
- location.symbol (optional)
- details.primary
 - fingerprint is the pipe-joined sequence: ruleKey|location.file|location.symbol|details.primary

## Outputs
- id: F-XXXXXXXX (Crockford Base32)

## Behavior
- IDs are derived from ruleKey, file, symbol, and details.primary
- IDs appear in state/reports/verify/latest.json and state/reports/review/latest.md

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq verify
```

## Exit Codes
- N/A

## Failure Cases
- Non-normalized paths in fingerprint
- Missing details.primary
