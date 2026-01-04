# CLI

Fabryq ships Symfony console commands (`fabryq:*`). Use them via `bin/console` from the project root.

## Usage

```bash
bin/console fabryq:<command> [args...]
```

## Global options

- `--debug`: show a stack trace on errors.

## Project configuration (`fabryq.yaml`)

Fabryq reads optional config from `fabryq.yaml` in the project root. If the file is missing, defaults apply.

Example:

```yaml
controller:
  route_prefix: ''
  route_name_prefix: ''
  default_format: 'json'
  security:
    enabled: false
    attribute: ''
  templates:
    enabled: false
    namespace: ''
  translations:
    enabled: false
    domain: 'messages'
reports:
  links:
    enabled: true
    scheme: 'phpstorm'
```

`reports.links.scheme` accepts `phpstorm`, `file`, or `none`.

## Exit codes

- `0`: success
- `2`: user error (invalid input)
- `3`: project state error (blockers, missing project files, or invalid config)
- `1`: internal error (unexpected)

Commands may return additional non-zero codes as documented below.

## Gate flow (high level)

```mermaid
flowchart TD
  Verify[verify] --> VerifyReports[state/reports/verify]
  Verify -->|blockers| VerifyFail[exit 3]
  Verify -->|no blockers| VerifyOk[exit 0]
  Verify --> Fix[fix]
  Fix --> Fixers[fix:assets / fix:crossing]
  Fixers --> FixLogs[state/fix]
  Doctor[doctor] --> DoctorReports[state/reports/doctor]
  Graph[graph] --> GraphReports[state/graph]
```

## Commands

### verify

Run all verification gates and write report artifacts.

Syntax:

```bash
bin/console fabryq:verify
```

Outputs:

- `state/reports/verify/latest.json`
- `state/reports/verify/latest.md`

Exit codes:

- `0`: no blockers (warnings allowed)
- `3`: blockers present

### review

Run verification and generate a review report grouped by rule key.

Syntax:

```bash
bin/console fabryq:review
```

Output:

- `state/reports/review/latest.md`

Exit codes:

- `0`: no blockers
- `3`: blockers present

### doctor

Evaluate consumed capabilities against resolver winners and app status.

Syntax:

```bash
bin/console fabryq:doctor
```

Outputs:

- `state/reports/doctor/latest.json`
- `state/reports/doctor/latest.md`

Exit codes:

- `0`: healthy
- `3`: blockers or unhealthy/degraded apps

### graph

Export the capability graph showing consumes, provider candidates, and winners.

Syntax:

```bash
bin/console fabryq:graph [--json] [--mermaid]
```

Options:

- `--json`: write `state/graph/latest.json`
- `--mermaid`: include Mermaid graph in `state/graph/latest.md`

Outputs:

- `state/graph/latest.md`
- `state/graph/latest.json` (with `--json`)

Exit codes:

- `0`: no missing winners and no degraded winners
- `3`: missing or degraded winners

### assets:install

Publish app and component assets to `public/fabryq`.

Syntax:

```bash
bin/console fabryq:assets:install [--dry-run]
```

Options:

- `--dry-run`: plan changes without writing files.

Outputs:

- `state/assets/manifest.json`
- `state/assets/latest.md`

Exit codes:

- `0`: success
- `3`: asset collisions detected

### app:create

Create a new app skeleton.

Syntax:

```bash
bin/console fabryq:app:create <AppPascal> [--app-id=<kebab>] [--mount=/<path>]
```

Options:

- `--app-id`: override the app id (defaults to kebab-case slug of name)
- `--mount`: mountpoint path (must start with `/`; no trailing `/` unless it is `/`; no `//`)
- `--dry-run`: plan changes without writing files

Outputs:

- `src/Apps/<AppPascal>/manifest.php`
- `src/Apps/<AppPascal>/Resources/{config,public,templates,translations}/.keep`

Exit codes:

- `0`: success
- `2`: invalid name or invalid app id
- `3`: mountpoint collision or existing app

### component:create

Create a new component within an app.

Syntax:

```bash
bin/console fabryq:component:create <AppPascal|appId> <ComponentPascal> [--with-public] [--with-templates] [--with-translations]
```

Options:

- `--with-public`: add `Resources/public`
- `--with-templates`: add `Resources/templates`
- `--with-translations`: add `Resources/translations`
- `--dry-run`: plan changes without writing files

Outputs:

- `src/Apps/<AppPascal>/<ComponentPascal>/{Controller,Service,Resources/config}`

Exit codes:

- `0`: success
- `2`: invalid component name
- `3`: app not found or slug collision

### crud:create

Create CRUD scaffolding for a resource inside an app.

Syntax:

```bash
bin/console fabryq:crud:create <AppPascal|appId> <ResourcePascal> [--dry-run]
```

Options:

- `--dry-run`: plan changes without writing files.

Outputs:

- `src/Apps/<AppPascal>/<ResourcePascal>/UseCase/<ResourcePascal>/<Action><ResourcePascal>UseCase.php`
- `src/Apps/<AppPascal>/<ResourcePascal>/Dto/<ResourcePascal>/<Action><ResourcePascal>{Request,Response}.php`
- `src/Apps/<AppPascal>/<ResourcePascal>/Controller/<ResourcePascal>Controller.php`

Exit codes:

- `0`: success
- `2`: invalid resource name
- `3`: app not found or existing target

### component:add:templates

Add templates scaffolding to a component.

Syntax:

```bash
bin/console fabryq:component:add:templates <ComponentPascal> [--dry-run]
```

Options:

- `--dry-run`: plan changes without writing files.

Outputs:

- `Resources/templates/.keep`

Exit codes:

- `0`: success
- `2`: ambiguous component name
- `3`: component not found

### component:add:translations

Add translations scaffolding to a component.

Syntax:

```bash
bin/console fabryq:component:add:translations <ComponentPascal> [--dry-run]
```

Options:

- `--dry-run`: plan changes without writing files.

Outputs:

- `Resources/translations/.keep`

Exit codes:

- `0`: success
- `2`: ambiguous component name
- `3`: component not found

### app:remove

Remove an application after dependency checks.

Syntax:

```bash
bin/console fabryq:app:remove <AppPascal|appId> [--dry-run]
```

Options:

- `--dry-run`: plan changes without writing files.

Exit codes:

- `0`: success
- `2`: invalid input
- `3`: blocked by existing references or app not found

### component:remove

Remove a component after dependency checks.

Syntax:

```bash
bin/console fabryq:component:remove <ComponentPascal> [--dry-run]
```

Options:

- `--dry-run`: plan changes without writing files.

Exit codes:

- `0`: success
- `2`: ambiguous component name
- `3`: blocked by existing references or component not found

### fix (dispatcher)

Dispatch autofixers based on available findings.

Syntax:

```bash
bin/console fabryq:fix --dry-run|--apply [--all|--file=<path>|--symbol=<symbol>|--finding=<id>]
```

Options:

- `--dry-run`: plan changes without writing
- `--apply`: apply changes to disk
- Selection (use only one): `--all` (default), `--file`, `--symbol`, `--finding`

Exit codes:

- `0`: success or nothing to fix
- `2`: invalid mode or selection
- `3`: fixer reported a project state error

### fix:assets

Plan or apply asset publishing, including collision handling.

Syntax:

```bash
bin/console fabryq:fix:assets --dry-run|--apply [--all|--file=<path>|--finding=<id>]
```

Notes:

- `--symbol` is not supported for this fixer.

Outputs:

- Fix plan and run logs under `state/fix/`
- Asset manifest under `state/assets/`

Exit codes:

- `0`: success
- `2`: invalid selection
- `3`: blocked plan or apply failure

### fix:crossing

Generate bridge contracts and adapters for cross-app references.

Syntax:

```bash
bin/console fabryq:fix:crossing --dry-run|--apply [--all|--file=<path>|--symbol=<symbol>|--finding=<id>] [--prune-unresolvable-imports]
```

Options:

- `--prune-unresolvable-imports`: remove unresolved imports when Composer autoload is available.

Outputs:

- Bridge component under `src/Components/Bridge<ProviderApp>/`
- Adapter in provider app under `Service/Bridge/`
- Updated consumer code and manifests
- Fix plan and run logs under `state/fix/`

Notes:

- Cross-app entity type hints are replaced with contracts interfaces; missing interfaces are created under `src/Apps/<ProviderApp>/Contracts/`.

Exit codes:

- `0`: success
- `2`: invalid selection
- `3`: blocked plan or apply failure

## Typical workflows

### CI gate

```bash
bin/console fabryq:verify
```

Fail the build if the exit code is non-zero.

### Resolve cross-app references

```bash
bin/console fabryq:verify
bin/console fabryq:fix:crossing --dry-run --finding=<F-ID>
bin/console fabryq:fix:crossing --apply --finding=<F-ID>
bin/console fabryq:verify
```

### Publish assets

```bash
bin/console fabryq:assets:install
```

If collisions exist:

```bash
bin/console fabryq:fix:assets --dry-run --all
bin/console fabryq:fix:assets --apply --all
```

### Review output for PRs

```bash
bin/console fabryq:review
```

## Related docs

- [Docs Index](INDEX.md)
- [Workflows](WORKFLOWS.md)
- [Guardrails](GUARDRAILS.md)
- [Troubleshooting](TROUBLESHOOTING.md)
