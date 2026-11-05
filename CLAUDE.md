# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer test                              # run the Pest suite (prints coverage, writes clover.xml)
vendor/bin/pest --no-coverage              # faster run without coverage
vendor/bin/pest --filter "implicit rules"  # run a single test
composer lint                              # check style (Pint --test) and static analysis (Larastan)
composer format                            # format with Laravel Pint
```

Tests are Pest on `orchestra/testbench`: `tests/Pest.php` binds `tests/TestCase.php`, which registers `ValidationServiceProvider` and provides a `validate($data, $rules, $messages)` helper. Static analysis is Larastan at level 5 (`phpstan.neon`, `src` only). CI is `.github/workflows/datalogix.yml`, which calls `datalogix/workflows/.github/workflows/package.yml@v2` (runs `composer test`, Pint and PHPStan). Supported: PHP ^8.2, Laravel 12 and 13, respect/validation ^2.5.

## Architecture

The package exposes every [Respect Validation](https://respect-validation.readthedocs.io) rule as a Laravel string rule (e.g. `'cpf'`, `'minAge:18'`, `'videoUrl:youtube'`) without registering each one individually.

- **`ValidationServiceProvider`** loads translations (namespace `laravel-validation`, publish tag `laravel-validation-lang`) and replaces the validator factory's resolver so every validator is a `Datalogix\Validation\Validator`. Only one resolver can exist, so another package calling `Validator::resolver()` conflicts with this one.
- **`Validator`** (extends Laravel's validator) intercepts `__call('validateXxx', ...)`. If the rule is not a Laravel built-in and not registered through `Validator::extend()`, it is resolved through `RuleFactory` and run with Respect's `validate()`. Laravel built-ins and extensions always take precedence over Respect rules with the same name; `isRespectRule()` encodes this check and is reused for messages and placeholders. `NotBlank`, `NotEmpty` and `NotOptional` are added to `implicitRules` so they run on empty values.
- **`RuleFactory`** maps a rule name to `Respect\Validation\Rules\{Name}`, applying `$alias` (e.g. `MinimumAge` → `MinAge`, `FileExists` → `Exists`; `Int`/`Bool` are only reachable through `RuleFactory::make()` because Laravel normalizes `int`/`bool` to `Integer`/`Boolean`). It excludes non-instantiable classes and rules whose constructors require objects (e.g. `each`, `not`), which cannot be expressed as string parameters. String parameters are cast to the constructor types: `""`/`"null"` (any case) → `null` for nullable parameters, values accepted by `FILTER_VALIDATE_BOOLEAN` (`true`/`false`, `1`/`0`, `on`/`off`, `yes`/`no`, any case) → bool (anything else, including an empty value, throws `InvalidArgumentException`), and for an `array` parameter the remaining parameters become the array (`containsAny:a,b`), so later parameters such as `containsAny`'s `$identical` can only be set through the array rule syntax (`[['containsAny', ['a'], true]]`), where a real array is used as is. Numeric values → `int` for `int` parameters (`length:1e1` is 10). An invalid number (`length:abc`, `length:5.5`, out of range), a value of the wrong type (`key:foo,bar`), an empty value for a typed parameter (`extension:,`, `countryCode:,`), an empty item in a string list (`subset:a,`) and `length` with both bounds empty (`length:,`) throw `InvalidArgumentException`. Untyped parameters accept empty values, so `equals:` compares with an empty value. `RuleFactory::cast()` and `RuleFactory::messageParameters()` are `@internal`, serve the messages and never throw, because `addFailure()` can pass any parameters. Results and reflection data are memoized in static arrays.

## Error messages

- Messages live in `lang/en/validation.php` and `lang/pt_BR/validation.php`, keyed by the snake_case rule name. When adding a message, add it to both locales; tests check that both locales have the same keys and placeholders and that every available rule has a message.
- English messages follow Laravel's "The :attribute field must ..." style.
- The application's `validation.{rule}` message wins; the package message is used when Laravel would return the raw key, or, for rules in `Validator::FRAMEWORK_KEY_RULES` (`uploaded`), any `validation.{rule}` line, including the application's (apps with published Laravel lang files define `uploaded` with Laravel's "failed to upload" meaning), unless the line is defined for a current locale the package does not ship; a published `validation.uploaded` in such a locale cannot be told apart, so it keeps Laravel's meaning (documented in the README). Those rules are overridden only through inline/`custom` messages or the published package translations.
- `length` with a single bound uses `length_min` (`length:10`) or `length_max` (`length:,5`), and exclusive bounds (`length:10,20,false`) add the `_exclusive` suffix (`length_exclusive`, `length_min_exclusive`, `length_max_exclusive`), and `length` without parameters uses `length_unbounded`, chosen in `Validator::lengthMessage()`, unless the application replaced the `length` message. The application's `validation.length_*` line wins over the package one.
- `base64` is a Laravel built-in since Laravel 13, so its package message only applies on Laravel 12; the `int` and `bool` keys are kept only for backward compatibility.
- Respect-specific placeholders, replaced in `Validator::makeReplacements()`: `:value`/`:min` = first parameter, `:max` = second parameter (or the first when there is only one), `:values` = all parameters joined by `, `. They use the converted parameters: skipped nullable parameters are empty in the first three and left out of `:values`, an array parameter shows only its values, parameters the constructor ignores are left out, and what cannot be shown (a closure) is empty.
- Issues and pull requests: English
