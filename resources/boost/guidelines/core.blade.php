## Laravel Validation (datalogix/laravel-validation)

This package exposes every [Respect Validation](https://respect-validation.readthedocs.io) 2.x rule as a Laravel string rule. Rules are resolved dynamically, so they do not appear in `AppServiceProvider` or in any rule list in the application.

- Before writing a custom `Rule` class or closure, check whether a Respect rule already covers the case (documents, phones, URLs, ages, countries and others). Use the rule name in camelCase.

@verbatim
<code-snippet name="Use Respect rules as string rules" lang="php">
$request->validate([
    'cpf' => ['required', 'cpf'],
    'birthdate' => ['required', 'minAge:18'],
    'video' => ['nullable', 'videoUrl:youtube'],
    'tags' => ['array', 'subset:php,laravel'],
]);
</code-snippet>
@endverbatim

### Rule resolution

- Laravel's built-in rules and rules registered with `Validator::extend()` take precedence over Respect rules with the same name (`email`, `numeric`, `regex`, `json`, `contains`, and `base64` on Laravel 13).
- Laravel turns `int` and `bool` into `integer` and `boolean`. Use `intVal` and `boolType` for the Respect rules.
- Rules that require other rules or objects (`each`, `not`, `allOf`, `key` with an inner rule) are not available as string rules. Use Laravel's `array.*` syntax or a custom `Rule` instead.
- `notBlank`, `notEmpty` and `notOptional` also run on empty or missing values.
- The package registers a validator resolver. Only one can exist, so do not call `Validator::resolver()` in the application or add another package that does.

### Parameters

Parameters are passed to the rule constructor in order and converted to its types. An invalid parameter throws an `InvalidArgumentException` when the rule runs, not a validation error.

- Booleans accept `true`/`false`, `1`/`0`, `on`/`off` and `yes`/`no` (`domain:false`).
- Numbers must be valid integers: `length:abc` and `length:5.5` throw.
- An empty value or `null` skips a nullable parameter (`length:,5`). `length:,`, `extension:,` and `subset:a,` throw.
- For an array parameter, the remaining parameters become the array (`containsAny:foo,bar`). Parameters after it can only be set with the array rule syntax.

@verbatim
<code-snippet name="Set a parameter after an array parameter" lang="php">
$request->validate([
    'title' => [['containsAny', ['foo', 'bar'], true]],
]);
</code-snippet>
@endverbatim

### Optional dependencies

- `iban` requires `ext-bcmath`.
- `mimetype` requires `ext-fileinfo`.
- `phone` with a country code (`phone:BR`) requires `giggsey/libphonenumber-for-php-lite`.

### Error messages

- The package ships messages in `en` and `pt_BR`. To override one, add the snake_case rule name to `lang/{locale}/validation.php` (`'video_url' => 'The :attribute field must be a video URL.'`).
- To publish the package translations, run `php artisan vendor:publish --tag=laravel-validation-lang`.
- Placeholders: `:value` and `:min` are the first parameter, `:max` is the second (or the first when there is only one), and `:values` is all parameters joined by commas.
