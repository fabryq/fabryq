# Workflows

## Create an app
```bash
bin/console fabryq:app:create Billing --mount=/billing
```

What it does:
- Creates `src/Apps/Billing/manifest.php` and resource directories.
- Validates `appId`, mountpoint, and uniqueness.

Next step: add a component.

## Add a component
```bash
bin/console fabryq:component:create Billing Payments --with-templates --with-public
```

What it does:
- Creates `Controller/`, `Service/`, and `Resources/config` under the component.
- Adds optional `Resources/templates` and `Resources/public`.

Add templates/translations later:
```bash
bin/console fabryq:component:add:templates Payments
bin/console fabryq:component:add:translations Payments
```

If multiple apps share the same component name, the command is ambiguous; rename or remove duplicates first.

## Generate CRUD scaffolding
```bash
bin/console fabryq:crud:create Billing Invoice
```

What it does:
- Creates `UseCase`, `Dto`, and `Controller` scaffolding for the resource inside the app.
- Applies `fabryq.yaml` controller defaults (route prefixes, security attributes, templates, translations).

## Add a capability provider (example)
1) Define a contract (interface) in a shared location (for cross-app usage, a global component is typical).
2) Implement it in a provider class and tag it with `#[FabryqProvider]`.

Example provider:
```php
use Fabryq\Runtime\Attribute\FabryqProvider;
use App\Components\BridgeInventory\Contract\StockServiceInterface;

#[FabryqProvider(capability: 'fabryq.bridge.inventory.stock-service', contract: StockServiceInterface::class)]
final class StockServiceAdapter implements StockServiceInterface
{
}
```

3) Declare the capability in the providing app manifest (`provides`).
4) Declare the capability in the consuming app manifest (`consumes`).

Run:
```bash
bin/console fabryq:verify
bin/console fabryq:doctor
```

## Remove an app
Plan the removal:
```bash
bin/console fabryq:app:remove Billing --dry-run
```

Remove the app:
```bash
bin/console fabryq:app:remove Billing
```

If other apps reference the target app, removal is blocked until references are removed.

If the app published assets, re-run:
```bash
bin/console fabryq:assets:install
```

Re-run gates:
```bash
bin/console fabryq:verify
bin/console fabryq:doctor
```

## Remove a component
Plan the removal:
```bash
bin/console fabryq:component:remove Payments --dry-run
```

Remove the component:
```bash
bin/console fabryq:component:remove Payments
```

If other apps/components reference the target component, removal is blocked until references are removed.

## Upgrade Fabryq (manual)
Fabryq does not ship an automated upgrade command.

For this repo (fresh setup):
```bash
composer create-project fabryq/fabryq:0.4.0 fabryq-project
cd fabryq-project
```

For an existing checkout:
```bash
git pull
composer install
```

Then run:
```bash
bin/console fabryq:verify
bin/console fabryq:doctor
```

For a project using Fabryq packages:
1) Update Composer constraints for `fabryq/*`.
2) Run `composer update`.
3) Re-run `verify`, `doctor`, and tests.
4) Review `CHANGELOG.md` for manual steps.

## CI smoke workflow
The repository ships a dedicated workflow to validate that the CLI commands execute in the demo project.

Expectations:
- `fabryq:verify`, `fabryq:doctor`, and `fabryq:graph` must succeed on the demo project.

Workflow file: `.github/workflows/fabryq-cli.yml`.

## Related docs
- [Docs Index](INDEX.md)
- [Getting Started](GETTING_STARTED.md)
- [CLI](CLI.md)
- [Guardrails](GUARDRAILS.md)
