# app:create

## Purpose
Generate a new application skeleton with manifest and resource folders.

## Inputs
- name (PascalCase folder name)
- --app-id (optional, defaults to slug of name)
- --mount (optional)

## Outputs
- src/Apps/<AppPascal>/manifest.php
- src/Apps/<AppPascal>/Resources/{config,public,templates,translations}/ with .keep

## Flags
- --app-id=<id>
- --mount=<path>

## Examples
```bash
vendor/bin/fabryq app:create Billing --app-id=billing --mount=/billing
```

## Exit Codes
- 0: success
- 1: validation or collision error

## Failure Cases
- appId not matching ^[a-z0-9]+(?:-[a-z0-9]+)*$
- mountpoint invalid (must start with /, no trailing / unless /, no //)
- appId or folder already exists
- mountpoint collision
