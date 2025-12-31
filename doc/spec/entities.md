# Entities

## Purpose
Provide a minimal entity abstraction for application and component domain models.

## Inputs
- Entity identifier (string, for example a ULID string)

## Outputs
- FabryqEntityInterface
- AbstractFabryqEntity

## Flags
- N/A

## Examples
```php
use Fabryq\Runtime\Entity\AbstractFabryqEntity;

final class Invoice extends AbstractFabryqEntity
{
    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
```

```php
use Fabryq\Runtime\Entity\FabryqEntityInterface;

final class LineItem implements FabryqEntityInterface
{
    public function __construct(private string $id)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

## Exit Codes
- N/A

## Failure Cases
- Entity identifier not initialized before access
- Hiding service dependencies in the entity
