# FABRYQ.ENTITY.BASE_REQUIRED

## Purpose
Enforce the Fabryq entity base for Doctrine entities.

## Inputs
- Project code under src/Apps/** and src/Components/** (Entity folders)

## Outputs
- Verification findings with ruleKey FABRYQ.ENTITY.BASE_REQUIRED
- details.primary formats:
  - <fqcn>|missing-base
  - <fqcn>|trait-exception

## Behavior
- Missing base produces BLOCKER
- Interface+trait exception produces WARNING

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq verify
```

## Exit Codes
- N/A

## Failure Cases
- Entity does not extend AbstractFabryqEntity
- Entity uses FabryqEntityInterface without FabryqEntityTrait
