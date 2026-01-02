# Rule: ENTITY_BASE_REQUIRED

## Purpose
Ensure Doctrine entities extend AbstractFabryqEntity or use the approved exception path.

## Inputs
- src/Apps/**/Entity/*.php
- src/Components/**/Entity/*.php

## Outputs
- FABRYQ.ENTITY.BASE_REQUIRED findings (BLOCKER or WARNING)

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq app:create Demo --mount=/demo
vendor/bin/fabryq component:create Demo Core
```
Add a Doctrine entity under the component Entity folder to see the rule enforced.

## Exit Codes
- N/A

## Failure Cases
- Entity does not extend AbstractFabryqEntity (BLOCKER)
- Entity uses interface + trait exception path (WARNING)

## Notes
- Exception path is allowed only when inheritance is impossible.
