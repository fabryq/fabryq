# Troubleshooting

## Wrapper cannot find the project root
Symptoms:
- `Unable to locate project root (composer.json + bin/console).`

Fix:
- Run `vendor/bin/fabryq` from inside a Symfony project that contains `composer.json` and `bin/console`.
- If you are inside a subdirectory, run the command from the project root.

## `FABRYQ.MANIFEST.INVALID`
Symptoms:
- Verify fails with missing keys or invalid manifest format.

Fix:
- Ensure `manifest.php` returns an array.
- Required keys: `appId`, `name`, `mountpoint`, `consumes`.

## `FABRYQ.APP.CROSSING`
Symptoms:
- Verify reports cross-app references.

Fix:
- Introduce a capability contract and provider.
- Use `fabryq fix crossing --dry-run --finding=<F-ID>` for auto-fixable cases.

## `FABRYQ.PUBLIC.COLLISION`
Symptoms:
- Asset targets overlap; verify fails.

Fix:
- Rename or move assets so targets are unique.
- Use `fabryq fix assets --dry-run --all` to review the plan.

## `FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN`
Symptoms:
- Verify blocks container typehints or `container->get()` usage.

Fix:
- Inject explicit dependencies or `FabryqContext` instead of a container.

## `FABRYQ.ENTITY.BASE_REQUIRED`
Symptoms:
- Verify reports entities missing the Fabryq base class.

Fix:
- Extend `AbstractFabryqEntity` or implement `FabryqEntityInterface` and use `FabryqEntityTrait`.

## `FABRYQ.DOCTRINE.TABLE_PREFIX`
Symptoms:
- Verify reports an explicit table name missing the `app_<appId>_` prefix.

Fix:
- Update the `#[ORM\Table(name: ...)]` attribute to include the prefix.

## `FABRYQ.CONSUME.REQUIRED.MISSING_PROVIDER`
Symptoms:
- Required capability has no provider.

Fix:
- Add a provider tagged with `#[FabryqProvider]`, or mark the consume as optional.

## `FABRYQ.CAPABILITY.MAP.MISSING`
Symptoms:
- `fabryq doctor` returns a blocker even in an empty project.

Fix:
- Register at least one provider so the capability map is not empty, or skip `doctor` until providers exist.
- Ensure `FabryqRuntimeBundle` is enabled so the compiler pass runs.

## Fix commands reject the mode
Symptoms:
- `Specify exactly one of --dry-run or --apply.`

Fix:
- Provide either `--dry-run` or `--apply` (not both).

## `fix crossing` is blocked by DTO rules
Symptoms:
- `fabryq fix crossing` reports a blocker about DTO safety.

Fix:
- Ensure the provider class is inside the provider app.
- DTO source classes must:
  - be in the provider app
  - have public, typed properties
  - avoid Doctrine annotations
  - have constructors that accept all properties

## Related docs
- [Docs Index](INDEX.md)
- [CLI](CLI.md)
- [Guardrails](GUARDRAILS.md)
- [Workflows](WORKFLOWS.md)
