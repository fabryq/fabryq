# Components

## Purpose
Define component layout for app-local and global components.

## Inputs
- Component name (PascalCase)
- App folder name (PascalCase)

## Outputs
- App component:
  - src/Apps/<AppPascal>/<ComponentPascal>/{Controller,Service}/
  - src/Apps/<AppPascal>/<ComponentPascal>/Resources/config/
  - Optional: Resources/public/, Resources/templates/, Resources/translations/
- Global component:
  - src/Components/<ComponentPascal>/...

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq component:create billing Invoices
```

## Exit Codes
- N/A

## Failure Cases
- ComponentName not matching ^[A-Z][A-Za-z0-9]*$
- Component folder already exists
- Component slug collision within the app
