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
```json
{
  "runId": "a1b2c3d4e5f6",
  "startedAt": "2025-01-01T00:00:00+00:00",
  "finishedAt": "2025-01-01T00:00:05+00:00",
  "mode": "apply",
  "fixer": "assets",
  "result": "ok",
  "counts": {
    "changedFiles": 2,
    "blockers": 0,
    "warnings": 0
  },
  "path": "state/fix/a1b2c3d4e5f6"
}
```

## Exit Codes
- N/A

## Failure Cases
- Existing plan differs from current plan
- Blockers prevent apply
