# fix:crossing

## Purpose
Fix cross-app references by generating bridges, contracts, adapters, and NoOp providers.

## Inputs
- Findings with ruleKey FABRYQ.APP.CROSSING
- Consumer file, provider class, and method usage

## Outputs
- Bridge structure under src/Components/Bridge<ProviderAppPascal>/
- Adapter in provider app Service/Bridge/
- NoOp provider with priority -1000
- Consumer rewired to contract injection
- Manifests updated (provides/consumes)
- Fix run logs under state/fix/

## Flags
- --dry-run
- --apply
- --all
- --file=<path>
- --symbol=<name>
- --finding=<id>

## Examples
```bash
vendor/bin/fabryq fix crossing --dry-run --finding=F-1A2B3C4D
vendor/bin/fabryq fix crossing --apply --finding=F-1A2B3C4D
```

## Exit Codes
- 0: success
- 1: blockers or invalid selection

## Failure Cases
- Reference kind not autofixable (static, extends, implements, trait, attribute)
- DTO safety checks fail
- Existing bridge files incompatible
- Manifest conflicts
- --finding does not resolve to exactly one crossing
