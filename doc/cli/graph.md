# graph

## Purpose
Export the capability graph showing consumes, provider candidates, and winners.

## Inputs
- Manifests consumes[]
- Resolver map: fabryq.capabilities.map

## Outputs
- state/graph/latest.md (Markdown)
- state/graph/latest.json (optional with --json)
- Optional Mermaid block in Markdown (with --mermaid)

## Flags
- --json
- --mermaid

## Examples
```bash
vendor/bin/fabryq graph
vendor/bin/fabryq graph --json --mermaid
```

## Exit Codes
- 0: no missing winners and no degraded winners
- 10: degraded (winner priority -1000)
- 20: missing winners

## Failure Cases
- Missing resolver map entries
