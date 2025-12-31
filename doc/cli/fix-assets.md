# fix:assets

## Purpose
Plan or apply asset publishing with collision detection and fix logging.

## Inputs
- Asset sources:
  - src/Apps/<App>/Resources/public/
  - src/Apps/<App>/<Component>/Resources/public/
  - src/Components/<Component>/Resources/public/

## Outputs
- public/fabryq/apps/<appId>/...
- public/fabryq/apps/<appId>/<componentSlug>/...
- public/fabryq/components/<componentSlug>/...
- state/assets/manifest.json (apply only)
- state/assets/latest.md (apply only)
- state/fix/<runId>/plan.md
- state/fix/<runId>/changes.json
- state/fix/latest.json
- state/fix/latest.md

## Flags
- --dry-run
- --apply
- --all
- --file=<path>
- --finding=<id>

## Examples
```bash
vendor/bin/fabryq fix assets --dry-run --all
vendor/bin/fabryq fix assets --apply --all
```

## Exit Codes
- 0: success
- 1: blockers or invalid selection

## Failure Cases
- Asset target collisions
- Existing plan differs from current plan
- Symbol selection is not supported for assets
