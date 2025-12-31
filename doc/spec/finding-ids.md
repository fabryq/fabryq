# Finding IDs

## Purpose
Define deterministic finding IDs for reports and fixes.

## Inputs
- ruleKey
- location.file (relative, forward slashes)
- location.symbol (optional)
- details.primary
 - fingerprint is the pipe-joined sequence: ruleKey|location.file|location.symbol|details.primary

## Outputs
- id: F-XXXXXXXX (Crockford Base32)

## Flags
- N/A

## Examples
Fingerprint:
```
FABRYQ.APP.CROSSING|src/Apps/Billing/Service/Foo.php|App\\Other\\Foo|App\\Other\\Foo|typehint
```

ID format:
```
F-1A2B3C4D
```

## Exit Codes
- N/A

## Failure Cases
- Non-normalized paths in fingerprint
- Missing details.primary
