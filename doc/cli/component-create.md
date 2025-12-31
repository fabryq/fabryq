# component:create

## Purpose
Generate a new component inside an existing app.

## Inputs
- app (PascalCase folder name or appId)
- component (PascalCase)

## Outputs
- src/Apps/<AppPascal>/<ComponentPascal>/{Controller,Service}/
- src/Apps/<AppPascal>/<ComponentPascal>/Resources/config/ (with .keep)
- Optional: Resources/public, Resources/templates, Resources/translations

## Flags
- --with-public
- --with-templates
- --with-translations

## Examples
```bash
vendor/bin/fabryq component:create Billing Invoices --with-templates
```

## Exit Codes
- 0: success
- 1: validation or collision error

## Failure Cases
- ComponentName not matching ^[A-Z][A-Za-z0-9]*$
- App not found
- Component folder already exists
- Component slug collision within app
