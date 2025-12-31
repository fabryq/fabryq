# doctor

## Purpose
Evaluate consumed capabilities against resolver winners.

## Inputs
- Manifests consumes[]
- Resolver map: fabryq.capabilities.map

## Outputs
- state/reports/doctor/latest.json
- state/reports/doctor/latest.md

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq doctor
```

## Exit Codes
- 0: HEALTHY (all consumes have real providers)
- 10: HEALTHY_DEGRADED (at least one capability via NoOp)
- 20: UNHEALTHY (missing winners / missing map / inconsistent manifests)
- 30: FATAL (technical error)

## Failure Cases
- Missing resolver map
- Missing provider winners
- Provider validation issues
