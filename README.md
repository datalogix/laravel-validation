# Laravel Validation

[![Latest Stable Version](https://poser.pugx.org/datalogix/laravel-validation/version)](https://packagist.org/packages/datalogix/laravel-validation)
[![Total Downloads](https://poser.pugx.org/datalogix/laravel-validation/downloads)](https://packagist.org/packages/datalogix/laravel-validation)
[![tests](https://github.com/datalogix/laravel-validation/workflows/tests/badge.svg)](https://github.com/datalogix/laravel-validation/actions)
[![codecov](https://codecov.io/gh/datalogix/laravel-validation/branch/main/graph/badge.svg)](https://codecov.io/gh/datalogix/laravel-validation)
[![License](https://poser.pugx.org/datalogix/laravel-validation/license)](https://packagist.org/packages/datalogix/laravel-validation)

> Laravel Validation is a package that brings the power of [Respect Validation](https://respect-validation.readthedocs.io).

## Installation

You can install the package via composer:

```bash
composer require datalogix/laravel-validation
```

The package will automatically register itself.

## Usage

```php
$rules = [
    'cpf'               => ['cpf'],
    'cnpj'              => ['cnpj'],
    'cnh'               => ['cnh'],
    'minimumAge'        => ['minimumAge:20'],
    'callback'          => ['callback:is_int'],
    'charset'           => ['charset:ASCII'],
    'consonant'         => ['consonant'],
    'vowel'             => ['vowel'],
    'alnum'             => ['alnum:-'],
    'digit'             => ['digit: '],
    'alpha'             => ['alpha'],
    'countryCode'       => ['countryCode'],
    'creditCard'        => ['digit', 'creditCard'],
    'domain'            => ['domain'],
    'directory'         => ['directory'],
    'fileExists'        => ['fileExists'],
    'equals'            => ['equals:banana'],
    'even'              => ['even'],
    'floatVal'          => ['floatVal'],
    'float'             => ['float'],
    'graph'             => ['graph'],
    'instance'          => ['instance:DateTime'],
    'int'               => ['int'],
    'leapDate'          => ['leapDate:Y-m-d'],
    'leapYear'          => ['leapYear'],
    'arrayVal'          => ['arrayVal'],
    'Arr'               => ['arr'],
    'multiple'          => ['multiple:3'],
    'negative'          => ['negative'],
    'noWhitespace'      => ['noWhitespace'],
    'nullValue'         => ['nullValue'],
    'objectType'        => ['objectType'],
    'odd'               => ['odd'],
    'perfectSquare'     => ['perfectSquare'],
    'positive'          => ['positive'],
    'primeNumber'       => ['primeNumber'],
    'punct'             => ['punct'],
    'readable'          => ['readable'],
    'roman'             => ['roman'],
    'slug'              => ['slug'],
    'space'             => ['space:b'],
    'tld'               => ['tld'],
    'version'           => ['version'],
    'xdigit'            => ['xdigit'],
    'writable'          => ['writable'],
    'alwaysValid'       => ['alwaysValid'],
    'boolType'          => ['boolType'],
    'youtube'           => ['videoUrl:youtube'],
    'vimeo'             => ['videoUrl:vimeo'],
    'video1'            => ['videoUrl'],
    'video2'            => ['videoUrl'],
    'age'               => ['minAge:18', 'maxAge:60'],
    'state'             => ['subdivisionCode:BR'],
];

$data = [
    'cpf'               => '22205417118',
    'cnpj'              => '68518321000116',
    'cnh'               => '02650306461',
    'minimumAge'        => '1990-11-13',
    'callback'          => 20,
    'charset'           => 'acucar',
    'consonant'         => 'dcfg',
    'vowel'             => 'aeiou',
    'alnum'             => 'banana-123',
    'digit'             => '120129 21212',
    'alpha'             => 'banana',
    'countryCode'       => 'BR',
    'creditCard'        => '5555666677778884',
    'domain'            => 'google.com.br',
    'directory'         => __DIR__,
    'fileExists'        => __FILE__,
    'equals'            => 'banana',
    'even'              => 8,
    'floatVal'          => 9.8,
    'float'             => 9.8,
    'graph'             => 'LKM@#$%4;',
    'instance'          => new \Datetime(),
    'int'               => 9,
    'leapDate'          => '1988-02-29',
    'leapYear'          => '1988',
    'arrayVal'          => ['Brazil'],
    'Arr'               => ['Brazil'],
    'multiple'          => '9',
    'negative'          => '-10',
    'noWhitespace'      => 'laravelBrazil',
    'nullValue'         => null,
    'objectType'        => new \stdClass(),
    'odd'               => 3,
    'perfectSquare'     => 25,
    'positive'          => 1,
    'primeNumber'       => 7,
    'punct'             => '&,.;[]',
    'readable'          => __FILE__,
    'roman'             => 'VI',
    'slug'              => 'laravel-brazil',
    'space'             => '              b      ',
    'tld'               => 'com',
    'version'           => '1.0.0',
    'xdigit'            => 'abc123',
    'writable'          => __FILE__,
    'alwaysValid'       => '@#$_',
    'boolType'          => \is_int(2),
    'youtube'           => 'http://youtu.be/l2gLWaGatFA',
    'vimeo'             => 'http://vimeo.com/33677985',
    'video1'            => 'https://youtu.be/l2gLWaGatFA',
    'video2'            => 'https://vimeo.com/33677985',
    'age'               => '1990-11-13',
    'state'             => 'SP',
];

$validator = \Illuminate\Support\Facades\Validator::make($data, $rules);

if ($validator->passes()) {
    // Do something
}
```

> [!NOTE]
> Laravel's built-in rules (`numeric`, `email`, `regex`, `file`, `json`, `ends_with`, `lowercase`, `uppercase`, `contains`, `string`, etc.) and rules registered with `Validator::extend()` always take precedence over Respect rules with the same name.

> [!WARNING]
> This package registers a custom validator resolver. If another package also
> calls `Validator::resolver()`, only the last one registered will be used.

## Error messages

The package ships with error messages in English (`en`) and Brazilian Portuguese (`pt_BR`). Messages defined in your application's `lang/{locale}/validation.php` take precedence, so you can override any of them:

```php
// lang/en/validation.php
'cpf' => 'The :attribute is not a valid CPF.',
```

You can also publish the package translations:

```bash
php artisan vendor:publish --tag=laravel-validation-lang
```

The placeholders are replaced with the rule parameters:

- `:value` and `:min`: the first parameter
- `:max`: the second parameter (or the first, when there is only one)
- `:values`: all parameters, separated by commas

## For more validation rules

See all available rules here:

https://respect-validation.readthedocs.io/en/latest/list-of-rules/

Repository of Respect Validation:

https://github.com/Respect/Validation
