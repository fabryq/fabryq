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
1) Delete the app directory, for example:
```bash
rm -rf src/Apps/Billing
```

2) Update any other app manifests that consume capabilities provided by the removed app. Otherwise, `FABRYQ.CONSUME.REQUIRED.MISSING_PROVIDER` will be a blocker.

3) Re-publish assets to remove stale targets:
```bash
bin/console fabryq:assets:install
```

4) Re-run gates:
```bash
bin/console fabryq:verify
bin/console fabryq:doctor
```

## Upgrade Fabryq (manual)
Fabryq does not ship an automated upgrade command.

For this repo (fresh setup):
```bash
composer create-project fabryq/fabryq fabryq-project -s dev
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

## Related docs
- [Docs Index](INDEX.md)
- [Getting Started](GETTING_STARTED.md)
- [CLI](CLI.md)
- [Guardrails](GUARDRAILS.md)
