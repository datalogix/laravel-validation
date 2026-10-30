# Laravel Validation

[![Latest Stable Version](https://poser.pugx.org/datalogix/laravel-validation/version)](https://packagist.org/packages/datalogix/laravel-validation)
[![Total Downloads](https://poser.pugx.org/datalogix/laravel-validation/downloads)](https://packagist.org/packages/datalogix/laravel-validation)
[![tests](https://github.com/datalogix/laravel-validation/actions/workflows/tests.yml/badge.svg)](https://github.com/datalogix/laravel-validation/actions/workflows/tests.yml)
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

### Rule names and parameters

- Use the Respect rule name in camelCase: `cpf`, `videoUrl:youtube`, `minAge:18`.
- Parameters are passed to the rule constructor, in order:
  - `true`/`false` for boolean parameters (`domain:false`); any other value throws an `InvalidArgumentException`.
  - an empty value or `null` skips a nullable parameter (`length:,5`).
  - for an array parameter, the remaining parameters become the array (`containsAny:foo,bar`, `subset:a,b`).
- Rules that require other rules or objects (`each`, `not`, `allOf`, `key` with an inner rule, ...) are not available as string rules.
- `notBlank`, `notEmpty` and `notOptional` also run on empty or missing values.

> [!NOTE]
> Laravel's built-in rules (`numeric`, `email`, `regex`, `file`, `image`, `json`, `ends_with`, `lowercase`, `uppercase`, `contains`, `string`, `base64` on Laravel 13, etc.) and rules registered with `Validator::extend()` always take precedence over Respect rules with the same name. Laravel also turns `int` and `bool` into `integer` and `boolean`; use `intVal` and `boolType` for the Respect rules.

> [!WARNING]
> This package registers a custom validator resolver. If another package also
> calls `Validator::resolver()`, only the last one registered will be used.

## Error messages

The package ships with error messages in English (`en`) and Brazilian Portuguese (`pt_BR`). Messages defined in your application's `lang/{locale}/validation.php` take precedence, so you can override any of them:

```php
// lang/en/validation.php
'cpf' => 'The :attribute field is not a valid CPF.',
```

You can also publish the package translations to `lang/vendor/laravel-validation`:

```bash
php artisan vendor:publish --tag=laravel-validation-lang
```

The placeholders are replaced with the rule parameters:

- `:value` and `:min`: the first parameter
- `:max`: the second parameter (or the first, when there is only one)
- `:values`: all parameters, separated by commas

`length` with a single bound uses the `length_min` (`length:10`) or `length_max` (`length:,5`) message, unless you replace the `length` message. The `uploaded` rule uses the package message instead of Laravel's "failed to upload" line, unless you pass a custom message for it.

## Available rules

Every [Respect Validation 2.x rule](https://respect-validation.readthedocs.io/en/2.4/08-list-of-rules-by-category/) can be used, written in camelCase (e.g. `VideoUrl` → `videoUrl`). See also the [Respect Validation repository](https://github.com/Respect/Validation).

## License

MIT License. See [LICENSE](LICENSE).
