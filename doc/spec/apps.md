# Apps

## Purpose
Define application layout and manifest fields for Fabryq apps.

## Inputs
- src/Apps/<AppPascal>/manifest.php (PHP array)
- Fields:
  - appId (string, kebab-case)
  - name (string)
  - mountpoint (string or null)
  - provides (array of {capabilityId, contract})
  - consumes (array of {capabilityId, required, contract})
  - events (optional: publishes[], subscribes[])

## Outputs
- App directory:
  - src/Apps/<AppPascal>/manifest.php
  - src/Apps/<AppPascal>/Resources/{config,public,templates,translations}/

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq app:create billing --mount=/billing
```

## Exit Codes
- N/A

## Failure Cases
- appId not matching ^[a-z0-9]+(?:-[a-z0-9]+)*$
- mountpoint invalid or colliding with another app
- manifest missing required keys
- composer.json missing autoload.psr-4 mapping for src/Apps/
