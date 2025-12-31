# Tests

## Purpose
Describe how to run the automated test suite for Fabryq.

## Inputs
- PHP >= 8.2
- Composer dependencies installed
- SQLite PDO extension (tests use in-memory SQLite)
- Write access to a temp directory for fixture projects

## Outputs
- PHPUnit results in the console

## Flags
- --filter <TestName>
- --testsuite <Name>

## Examples
```bash
composer install
vendor/bin/phpunit
vendor/bin/phpunit --filter EntityLifecycleTest
vendor/bin/phpunit --filter VerifyRulesTest
```

## Exit Codes
- 0: all tests passed
- 1: failures or errors

## Failure Cases
- Dependencies missing (composer install not run)
- PHP version below 8.2
- SQLite extension missing
- Fixture generation failed (temp directory or wrapper missing)
