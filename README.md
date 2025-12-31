# Fabryq

![Fabryq Logo](doc/assets/logo.svg)

Fabryq is a PHP architecture toolkit with a CLI, runtime, and generators for deterministic app structures and reports.

## Requirements
- PHP >= 8.2

## Installation
```bash
composer install
```

## Usage
Run commands from a Fabryq project (skeleton or app repo):
```bash
vendor/bin/fabryq verify
vendor/bin/fabryq review
vendor/bin/fabryq app:create billing --mount=/billing
vendor/bin/fabryq component:create billing Checkout
```

## Development
```bash
vendor/bin/phpunit
```

## Docs
- doc/README.md

## License
Proprietary. See package metadata for details.

## Contributing
Contact the maintainers for contribution guidelines.
