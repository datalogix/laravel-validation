# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer test                              # run the Pest suite (writes clover.xml)
composer test -- --coverage                # also print the coverage summary
vendor/bin/pest --no-coverage              # faster run without coverage
vendor/bin/pest --filter "implicit rules"  # run a single test
composer lint                              # check style (Pint --test) and static analysis (Larastan)
composer format                            # format with Laravel Pint
```

## Project setup

- **Tests:** Pest on `orchestra/testbench`. `tests/Pest.php` binds `tests/TestCase.php`, which registers `ValidationServiceProvider` and provides a `validate($data, $rules, $messages)` helper.
- **Static analysis:** Larastan at level 5 (`phpstan.neon.dist`, `src` only).
- **CI:** `.github/workflows/datalogix.yml`, which calls `datalogix/workflows/.github/workflows/package.yml@v2` (runs `composer test`, Pint and PHPStan).
- **Supported:** PHP ^8.2, Laravel 12 and 13, respect/validation ^2.5.
- **Issues and pull requests:** English.

## Architecture

The package exposes every [Respect Validation](https://respect-validation.readthedocs.io) rule as a Laravel string rule (e.g. `'cpf'`, `'minAge:18'`, `'videoUrl:youtube'`) without registering each one individually.

### `ValidationServiceProvider`

- Loads translations (namespace `laravel-validation`, publish tag `laravel-validation-lang`).
- Replaces the validator factory's resolver so every validator is a `Datalogix\Validation\Validator`. Only one resolver can exist, so another package calling `Validator::resolver()` conflicts with this one.

### `Validator`

- Extends Laravel's validator and intercepts `__call('validateXxx', ...)`. If the rule is not a Laravel built-in and not registered through `Validator::extend()`, it is resolved through `RuleFactory` and run with Respect's `validate()`.
- Laravel built-ins and extensions always take precedence over Respect rules with the same name. `isRespectRule()` encodes this check and is reused for messages and placeholders.
- `NotBlank`, `NotEmpty` and `NotOptional` are added to `implicitRules` so they run on empty values.

### `RuleFactory`

- Maps a rule name to `Respect\Validation\Rules\{Name}`, applying `$alias` (e.g. `MinimumAge` → `MinAge`, `FileExists` → `Exists`). `Int`/`Bool` are only reachable through `RuleFactory::make()` because Laravel normalizes `int`/`bool` to `Integer`/`Boolean`.
- Excludes non-instantiable classes and rules whose constructors require objects (e.g. `each`, `not`), which cannot be expressed as string parameters.
- `RuleFactory::cast()` and `RuleFactory::messageParameters()` are `@internal`, serve the messages and never throw, because `addFailure()` can pass any parameters.
- Results and reflection data are memoized in static arrays.

String parameters are cast to the constructor types:

| Parameter type | Conversion |
| --- | --- |
| Nullable | `""`/`"null"` (any case) → `null` |
| `bool` | Values accepted by `FILTER_VALIDATE_BOOLEAN` (`true`/`false`, `1`/`0`, `on`/`off`, `yes`/`no`, any case) → bool |
| `int` | Numeric values → `int` (`length:1e1` is 10) |
| `array` | The remaining parameters become the array (`containsAny:a,b`); a real array from the array rule syntax is used as is |
| Untyped | Accepts empty values, so `equals:` compares with an empty value |

Later parameters after an array, such as `containsAny`'s `$identical`, can only be set through the array rule syntax (`[['containsAny', ['a'], true]]`).

These throw `InvalidArgumentException`:

- A boolean parameter with any other value, including an empty one.
- An invalid number (`length:abc`, `length:5.5`, out of range).
- A value of the wrong type (`key:foo,bar`).
- An empty value for a typed parameter (`extension:,`, `countryCode:,`).
- An empty item in a string list (`subset:a,`).
- `length` with both bounds empty (`length:,`).

## Error messages

### Files and style

- Messages live in `lang/en/validation.php` and `lang/pt_BR/validation.php`, keyed by the snake_case rule name.
- When adding a message, add it to both locales. Tests check that both locales have the same keys and placeholders and that every available rule has a message.
- English messages follow Laravel's "The :attribute field must ..." style.
- `base64` is a Laravel built-in since Laravel 13, so its package message only applies on Laravel 12.
- The `int` and `bool` keys are kept only for backward compatibility.

### Message precedence

- The application's `validation.{rule}` message wins. The package message is used when Laravel would return the raw key.
- For rules in `Validator::FRAMEWORK_KEY_RULES` (`uploaded`), the package message also replaces any `validation.{rule}` line, including the application's, because apps with published Laravel lang files define `uploaded` with Laravel's "failed to upload" meaning.
- The exception is a line defined for a current locale the package does not ship. A published `validation.uploaded` in such a locale cannot be told apart, so it keeps Laravel's meaning (documented in the README).
- Those rules are overridden only through inline/`custom` messages or the published package translations.

### `length` messages

`Validator::lengthMessage()` chooses the key from the bounds (see the table in the README, "The `length` rule"), unless the application replaced the `length` message. The application's `validation.length_*` line wins over the package one.

### Placeholders

Respect-specific placeholders are replaced in `Validator::makeReplacements()`:

- `:value`/`:min`: first parameter.
- `:max`: second parameter (or the first when there is only one).
- `:values`: all parameters joined by `, `.

They use the converted parameters:

- Skipped nullable parameters are empty in the first three and left out of `:values`.
- An array parameter shows only its values.
- Parameters the constructor ignores are left out.
- What cannot be shown (a closure) is empty.

## Laravel Boost

`resources/boost/guidelines/core.blade.php` is the AI guideline Laravel Boost installs in applications that select this package.

- **Keep it in sync:** it repeats behavior documented in the README (rule resolution, parameter conversion, optional dependencies, messages), and no test checks it. Update it whenever that behavior changes.
- **Code snippets:** keep them inside `@verbatim`.
- **Trying it:** run `boost:install` from a separate Laravel application. Boost writes to paths relative to the current directory, so running it from this repository changes this file.
