# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer test                                   # run the PHPUnit suite (prints coverage, writes clover.xml)
vendor/bin/phpunit --no-coverage                # faster run without coverage
vendor/bin/phpunit --filter test_implicit_rules # run a single test
composer lint                                   # format with Laravel Pint
```

Tests use `orchestra/testbench`; `tests/TestCase.php` registers `ValidationServiceProvider` and provides a `validate($data, $rules, $messages)` helper. CI runs the shared workflow `datalogix/workflows/.github/workflows/laravel-tests.yml@v1`. Supported: PHP ^8.2, Laravel 12 and 13.

## Architecture

The package exposes every [Respect Validation](https://respect-validation.readthedocs.io) rule as a Laravel string rule (e.g. `'cpf'`, `'minAge:18'`, `'videoUrl:youtube'`) without registering each one individually.

- **`ValidationServiceProvider`** loads translations (namespace `laravel-validation`, publish tag `laravel-validation-lang`) and replaces the validator factory's resolver so every validator is a `Datalogix\Validation\Validator`. Only one resolver can exist, so another package calling `Validator::resolver()` conflicts with this one.
- **`Validator`** (extends Laravel's validator) intercepts `__call('validateXxx', ...)`. If the rule is not a Laravel built-in and not registered through `Validator::extend()`, it is resolved through `RuleFactory` and run with Respect's `validate()`. Laravel built-ins and extensions always take precedence over Respect rules with the same name; `isRespectRule()` encodes this check and is reused for messages and placeholders. `NotBlank`, `NotEmpty` and `NotOptional` are added to `implicitRules` so they run on empty values.
- **`RuleFactory`** maps a rule name to `Respect\Validation\Rules\{Name}`. It applies `$alias` (e.g. `Int` → `IntVal`, `MinimumAge` → `MinAge`), excludes `Abstract*` classes and rules whose constructors require objects (e.g. other rules), which cannot be expressed as string parameters. It also casts `"true"`/`"false"` strings to booleans for `bool` constructor parameters. Results and reflection data are memoized in static arrays.

## Error messages

- Messages live in `lang/en/validation.php` and `lang/pt_BR/validation.php`, keyed by the snake_case rule name. When adding a message, add it to both locales.
- The application's `validation.{rule}` message wins; the package message is used only when Laravel would otherwise return the raw key.
- Respect-specific placeholders, replaced in `Validator::makeReplacements()`: `:value`/`:min` = first parameter, `:max` = second parameter (or the first when there is only one), `:values` = all parameters joined by `, `.
