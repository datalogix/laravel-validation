# Laravel Validation

[![Latest Stable Version](https://poser.pugx.org/datalogix/laravel-validation/version)](https://packagist.org/packages/datalogix/laravel-validation)
[![Total Downloads](https://poser.pugx.org/datalogix/laravel-validation/downloads)](https://packagist.org/packages/datalogix/laravel-validation)
[![datalogix](https://github.com/datalogix/laravel-validation/actions/workflows/datalogix.yml/badge.svg)](https://github.com/datalogix/laravel-validation/actions/workflows/datalogix.yml)
[![codecov](https://codecov.io/gh/datalogix/laravel-validation/branch/main/graph/badge.svg)](https://codecov.io/gh/datalogix/laravel-validation)
[![License](https://poser.pugx.org/datalogix/laravel-validation/license)](https://packagist.org/packages/datalogix/laravel-validation)

> Laravel Validation is a package that brings the power of [Respect Validation](https://respect-validation.readthedocs.io).

## Installation

You can install the package via composer:

```bash
composer require datalogix/laravel-validation
```

The package will automatically register itself.

## Requirements

- PHP 8.2+
- Laravel 12 or 13

Some rules need optional dependencies:

| Rule | Requires |
| --- | --- |
| `iban` | `ext-bcmath` |
| `mimetype` | `ext-fileinfo` |
| `phone` with a country code (`phone:BR`) | `composer require giggsey/libphonenumber-for-php-lite` |

## Usage

Use any Respect Validation rule as a Laravel string rule:

```php
$request->validate([
    'cpf' => ['required', 'cpf'],
    'birthdate' => ['required', 'minAge:18'],
    'video' => ['nullable', 'videoUrl:youtube'],
    'tags' => ['array', 'subset:php,laravel'],
]);
```

### Rule names

- Use the Respect rule name in camelCase: `cpf`, `videoUrl:youtube`, `minAge:18`.
- Rules that require other rules or objects (`each`, `not`, `allOf`, `key` with an inner rule, ...) are not available as string rules.
- `notBlank`, `notEmpty` and `notOptional` also run on empty or missing values.

> [!NOTE]
> Laravel's built-in rules (`numeric`, `email`, `regex`, `file`, `image`, `json`, `ends_with`, `lowercase`, `uppercase`, `contains`, `string`, `base64` on Laravel 13, etc.) and rules registered with `Validator::extend()` always take precedence over Respect rules with the same name.
>
> Laravel also turns `int` and `bool` into `integer` and `boolean`; use `intVal` and `boolType` for the Respect rules.

> [!WARNING]
> This package registers a custom validator resolver. If another package also
> calls `Validator::resolver()`, only the last one registered will be used.

### Rule parameters

Parameters are passed to the rule constructor, in order, and converted to its types:

| Parameter type | Accepted values | Example |
| --- | --- | --- |
| Boolean | `true`/`false`, `1`/`0`, `on`/`off`, `yes`/`no` | `domain:false` |
| Numeric | Numbers | `length:1e1` is 10 |
| Nullable | An empty value or `null` (any case) skips the parameter | `length:,5` |
| Array | The remaining parameters become the array | `containsAny:foo,bar`, `subset:a,b` |
| Untyped | Any value, including an empty one | `equals:` compares with an empty value |

An array given through the array rule syntax is used as is (`[['subset', ['a', 'b']]]`).

These throw an `InvalidArgumentException`:

- Any other value for a boolean parameter, including an empty one.
- Invalid numbers and integers out of range (`length:abc`, `length:5.5`).
- An empty value for any other typed parameter (`extension:,`, `countryCode:,`).
- An empty item in a list (`subset:a,`).
- `length` with both bounds empty (`length:,`).

## Error messages

The package ships with error messages in English (`en`) and Brazilian Portuguese (`pt_BR`).

### Overriding messages

Messages defined in your application's `lang/{locale}/validation.php` take precedence, so you can override any of them:

```php
// lang/en/validation.php
'cpf' => 'The :attribute field is not a valid CPF.',
```

You can also publish the package translations to `lang/vendor/laravel-validation`:

```bash
php artisan vendor:publish --tag=laravel-validation-lang
```

### Placeholders

The placeholders are replaced with the rule parameters:

- `:value` and `:min`: the first parameter
- `:max`: the second parameter (or the first, when there is only one)
- `:values`: all parameters, separated by commas

Skipped parameters are left out of `:values` and replaced with an empty value in `:value`, `:min` and `:max`, and an array parameter shows only its values.

### The `length` rule

The message depends on the bounds, unless you replace the `length` message:

| Bounds | Inclusive | Exclusive (`length:10,20,false`) |
| --- | --- | --- |
| Both | `length` | `length_exclusive` |
| Minimum only (`length:10`) | `length_min` | `length_min_exclusive` |
| Maximum only (`length:,5`) | `length_max` | `length_max_exclusive` |
| None (`length`) | `length_unbounded` | — |

You can override these messages with the same keys in your `lang/{locale}/validation.php`.

### The `uploaded` rule

The `uploaded` rule uses the package message instead of Laravel's "failed to upload" line unless you pass a custom message for it or define the line in a locale the package does not ship (it ships `en` and `pt_BR`).

The package cannot tell your line from one published by a translation package, so a published `validation.uploaded` in another locale keeps Laravel's "failed to upload" meaning; replace that line or pass a custom message.

## Available rules

Every [Respect Validation 2.x rule](https://respect-validation.readthedocs.io/en/2.4/08-list-of-rules-by-category/) can be used, written in camelCase (e.g. `VideoUrl` → `videoUrl`). See also the [Respect Validation repository](https://github.com/Respect/Validation).

## Laravel Boost

The package ships an AI guideline for [Laravel Boost](https://laravel.com/docs/boost). To install it, select `datalogix/laravel-validation` when `php artisan boost:install` asks for third-party guidelines.

With `--no-interaction`, Boost only includes the packages listed in `boost.json`, so add the package to its `packages` key first:

```json
{
    "packages": ["datalogix/laravel-validation"]
}
```

## License

MIT License. See [LICENSE](LICENSE).
