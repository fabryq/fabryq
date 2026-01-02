# Entities

## Purpose
Define the standard entity base and lifecycle expectations.

## Inputs
- AbstractFabryqEntity or FabryqEntityInterface + FabryqEntityTrait

## Outputs
- Entities with deterministic ULID ids and lifecycle timestamps

## Flags
- N/A

## Examples
```bash
vendor/bin/fabryq app:create Billing --mount=/billing
vendor/bin/fabryq component:create Billing Ledger
```
Add entities under the component Entity directory and extend AbstractFabryqEntity.

## Exit Codes
- N/A

## Failure Cases
- createdAt is null on persist (BLOCKER)
- Entity does not extend AbstractFabryqEntity (BLOCKER)
- Trait-based exception without interface + trait (WARNING)

## Fields
- id: ULID string (Base32)
- createdAt: DateTimeImmutable (not null)
- updatedAt: DateTimeImmutable|null
- deletedAt: DateTimeImmutable|null
- archivedAt: DateTimeImmutable|null

## Lifecycle
- PrePersist: createdAt always set, updatedAt initialized
- PreUpdate: updatedAt set

## archivedAt Semantics
- archive(): sets archivedAt
- unarchive(): clears archivedAt
- isArchived(): true when archivedAt is set
