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
```json
{
  "header": {
    "tool": "verify",
    "version": "0.2",
    "generatedAt": "2025-01-01T00:00:00+00:00",
    "result": "ok",
    "summary": {
      "blockers": 0,
      "warnings": 1
    }
  },
  "findings": [
    {
      "id": "F-1A2B3C4D",
      "ruleKey": "FABRYQ.APP.CROSSING",
      "severity": "BLOCKER",
      "message": "App Billing references App.Other.Foo.",
      "location": {
        "file": "src/Apps/Billing/Invoice/Service/Foo.php",
        "line": 12,
        "symbol": "App\\Other\\Foo"
      },
      "details": {
        "primary": "App\\Other\\Foo|typehint"
      },
      "autofix": {
        "available": true,
        "fixer": "crossing"
      }
    }
  ]
}
```

## Exit Codes
- N/A

## Failure Cases
- Missing required fields (id, ruleKey, severity, message, location, details.primary, autofix.available)
