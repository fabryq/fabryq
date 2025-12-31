# Entities

## Purpose
Define the standard entity base, lifecycle timestamps, and archive markers.

## Inputs
- Entity identifier (ULID string)
- createdAt/updatedAt timestamps (DateTimeImmutable)
- deletedAt/archivedAt markers (nullable)

## Outputs
- FabryqEntityInterface
- AbstractFabryqEntity
- FabryqEntityTrait (exception path)

## Behavior
- createdAt is set on persist and never null
- updatedAt is set on persist and update
- createdAt/updatedAt use immutable datetime types
- getId returns the ULID Base32 string
- archivedAt is an optional marker (archive/unarchive/isArchived)
- deletedAt is a soft-delete marker only
- Exception path: implement FabryqEntityInterface, use FabryqEntityTrait, add #[ORM\HasLifecycleCallbacks]

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq app:create billing --mount=/billing
vendor/bin/fabryq component:create billing Invoices
```

## Exit Codes
- N/A

## Failure Cases
- Entity does not extend AbstractFabryqEntity (or trait-based exception)
- createdAt not set before persistence
- archivedAt used as a delete marker
