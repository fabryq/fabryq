# Reports

## Purpose
Define report schemas for verify and review outputs.

## Inputs
- Findings emitted by analyzers (ruleKey, severity, message, location, details, autofix)

## Outputs
- state/reports/verify/latest.json (required)
- state/reports/verify/latest.md (optional)
- state/reports/review/latest.md (required)

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq verify
vendor/bin/fabryq review
```

## Exit Codes
- N/A

## Failure Cases
- Missing required fields (id, ruleKey, severity, message, location, details.primary, autofix.available)
