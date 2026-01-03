# Guardrails

Guardrails are enforced rules that produce findings during `bin/console fabryq:verify` and `bin/console fabryq:doctor`. Blockers fail the command; warnings do not.

## Rule index
| Rule key | Severity | Gate | Summary |
| --- | --- | --- | --- |
| `FABRYQ.MANIFEST.INVALID` | BLOCKER | verify | Manifest must return a valid array and required keys. |
| `FABRYQ.APP_ID.INVALID` | BLOCKER | verify | `appId` must be kebab-case. |
| `FABRYQ.MOUNTPOINT.INVALID` | BLOCKER | verify | `mountpoint` must start with `/` and not end with `/` (except `/`). |
| `FABRYQ.MOUNTPOINT.COLLISION` | BLOCKER | verify | Two apps share the same mountpoint. |
| `FABRYQ.COMPONENT.SLUG.INVALID` | BLOCKER | verify | Component slug derived from name is invalid. |
| `FABRYQ.COMPONENT.SLUG.COLLISION` | BLOCKER | verify | Two components in one app share a slug. |
| `FABRYQ.CAPABILITY.ID.INVALID` | WARNING | verify | Capability id is not namespaced (dot-separated). |
| `FABRYQ.APP.CROSSING` | BLOCKER | verify | App references another app class. |
| `FABRYQ.GLOBAL_COMPONENT.REFERENCES_APP` | BLOCKER | verify | Global component references app classes. |
| `FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN` | BLOCKER | verify | Container or service locator usage detected. |
| `FABRYQ.ENTITY.BASE_REQUIRED` | BLOCKER/WARNING | verify | Doctrine entity must use Fabryq base. |
| `FABRYQ.PUBLIC.COLLISION` | BLOCKER | verify | Asset targets collide. |
| `FABRYQ.DOCTRINE.TABLE_PREFIX` | BLOCKER | verify | Explicit entity table name missing app prefix. |
| `FABRYQ.PROVIDER.INVALID` | BLOCKER | verify/doctor | Provider metadata or implementation invalid. |
| `FABRYQ.CONSUME.REQUIRED.MISSING_PROVIDER` | BLOCKER | verify/doctor | Required capability has no provider. |
| `FABRYQ.CAPABILITY.MAP.MISSING` | BLOCKER | doctor | Capability resolver map missing. |
| `FABRYQ.CONSUME.OPTIONAL.MISSING_PROVIDER` | WARNING | doctor | Optional capability has no provider. |

## Rules and fixes

### Manifests and mountpoints
- `FABRYQ.MANIFEST.INVALID`: `manifest.php` must return an array with required keys (`appId`, `name`, `mountpoint`, `consumes`).
- `FABRYQ.APP_ID.INVALID`: `appId` must match `^[a-z0-9]+(?:-[a-z0-9]+)*$`.
- `FABRYQ.MOUNTPOINT.INVALID`: mountpoint must start with `/` and must not end with `/` unless it is `/`.
- `FABRYQ.MOUNTPOINT.COLLISION`: each app mountpoint must be unique.

Fix:
- Correct the manifest array and rerun `bin/console fabryq:verify`.

### Component slugs
- `FABRYQ.COMPONENT.SLUG.INVALID`: component slug derived from directory name is invalid.
- `FABRYQ.COMPONENT.SLUG.COLLISION`: two components in the same app resolve to the same slug.

Fix:
- Rename the component directories to unique, slug-safe names.

### Cross-app references
- `FABRYQ.APP.CROSSING`: app code references another app class (any `App\OtherApp\...`).
- `FABRYQ.GLOBAL_COMPONENT.REFERENCES_APP`: global components must not reference `App\...` classes.

Fix:
- Move shared code into a global component, or introduce a capability contract and provider.
- Use `fabryq fix crossing --dry-run` for auto-fixable references (`use`, `typehint`, `new`).

Do:
```php
use App\Components\BridgeInventory\Contract\StockServiceInterface;

final class CheckoutService
{
    public function __construct(private StockServiceInterface $stock) {}
}
```

Don't:
```php
use App\Inventory\Warehouse\Service\StockService;
```

### Service locator usage
- `FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN`: container typehints, `container->get()`, and `static::getContainer()` are blocked.

Fix:
- Inject explicit dependencies or `FabryqContext`.

Do:
```php
final class BillingService
{
    public function __construct(private FabryqContext $ctx) {}
}
```

Don't:
```php
public function __construct(private ContainerInterface $container) {}
```

### Entities and Doctrine
- `FABRYQ.ENTITY.BASE_REQUIRED`: Doctrine entities must extend `AbstractFabryqEntity` or implement `FabryqEntityInterface` with `FabryqEntityTrait` (warning).
- `FABRYQ.DOCTRINE.TABLE_PREFIX`: explicit entity table names must be prefixed with `app_<appId>_`.

Fix:
- Extend `AbstractFabryqEntity` or use the interface+trait exception path.
- Update `#[ORM\Table(name: 'app_<appId>_<table>')]` if you set an explicit table name.

Do:
```php
#[ORM\Entity]
#[ORM\Table(name: 'app_billing_invoice')]
final class Invoice extends AbstractFabryqEntity
{
}
```

Don't:
```php
#[ORM\Entity]
final class Invoice
{
}
```

### Capability wiring
- `FABRYQ.PROVIDER.INVALID`: provider is missing metadata, contract is not an interface, or provider does not implement the contract.
- `FABRYQ.CONSUME.REQUIRED.MISSING_PROVIDER`: required capability has no winner.
- `FABRYQ.CAPABILITY.ID.INVALID`: capability ids must be dot-separated (warning).
- `FABRYQ.CAPABILITY.MAP.MISSING`: no resolver map produced while apps exist.

Fix:
- Add or correct `#[FabryqProvider]` attributes.
- Ensure provider classes implement their contracts.
- Use namespaced capability ids like `fabryq.bridge.core.http-client`.
- Ensure at least one provider is registered; otherwise `doctor` reports `FABRYQ.CAPABILITY.MAP.MISSING` even in empty projects.
- Ensure the runtime bundle is enabled so the capability map is built.

### Assets
- `FABRYQ.PUBLIC.COLLISION`: multiple asset sources map to the same target.

Fix:
- Rename or move assets to avoid collisions.
- Use `fabryq fix assets --dry-run` to review the plan.

## Related docs
- [Docs Index](INDEX.md)
- [CLI](CLI.md)
- [Concepts](CONCEPTS.md)
- [Troubleshooting](TROUBLESHOOTING.md)
