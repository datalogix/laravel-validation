<?php

use Datalogix\Validation\RuleFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator as BaseValidator;
use Respect\Validation\Exceptions\ComponentException;

test('common rules', function () {
    $rules = [
        'phone' => ['phone'],
        'cpf' => ['cpf'],
        'cnpj' => ['cnpj'],
        'cnh' => ['cnh'],
        'minimumAge' => ['minimumAge:20'],
        'callback' => ['callback:is_int'],
        'charset' => ['charset:ASCII'],
        'consonant' => ['consonant'],
        'vowel' => ['vowel'],
        'alnum' => ['alnum:-'],
        'digit' => ['digit: '],
        'alpha' => ['alpha'],
        'containsArray' => ['contains:banana'],
        'contains' => ['contains:banana'],
        'countryCode' => ['countryCode'],
        'creditCard' => ['digit', 'creditCard'],
        'domain' => ['domain'],
        'directory' => ['directory'],
        'fileExists' => ['fileExists'],
        'endsWith' => ['endsWith:banana'],
        'equals' => ['equals:banana'],
        'even' => ['even'],
        'floatVal' => ['floatVal'],
        'float' => ['float'],
        'graph' => ['graph'],
        'instance' => ['instance:DateTime'],
        'int' => ['int'],
        'json' => ['json'],
        'leapDate' => ['leapDate:Y-m-d'],
        'leapYear' => ['leapYear'],
        'arrayVal' => ['arrayVal'],
        'Arr' => ['arr'],
        'lowercase' => ['lowercase'],
        'macAddress' => ['macAddress'],
        'multiple' => ['multiple:3'],
        'negative' => ['negative'],
        'noWhitespace' => ['noWhitespace'],
        'nullValue' => ['nullValue'],
        'numeric' => ['numeric'],
        'objectType' => ['objectType'],
        'odd' => ['odd'],
        'perfectSquare' => ['perfectSquare'],
        'positive' => ['positive'],
        'primeNumber' => ['primeNumber'],
        'punct' => ['punct'],
        'readable' => ['readable'],
        'regex' => ['regex:/5/'],
        'roman' => ['roman'],
        'slug' => ['slug'],
        'space' => ['space:b'],
        'tld' => ['tld'],
        'uppercase' => ['uppercase'],
        'version' => ['version'],
        'xdigit' => ['xdigit'],
        'writable' => ['writable'],
        'alwaysValid' => ['alwaysValid'],
        'boolType' => ['boolType'],
        'youtube' => ['videoUrl:youtube'],
        'vimeo' => ['videoUrl:vimeo'],
        'video1' => ['videoUrl'],
        'video2' => ['videoUrl'],
        'email' => ['email'],
        'age' => ['minAge:18', 'maxAge:60'],
        'state' => ['subdivisionCode:BR'],
    ];

    $data = [
        'phone' => '+1 650 253 00 00',
        'cpf' => '22205417118',
        'cnpj' => '68518321000116',
        'cnh' => '02650306461',
        'minimumAge' => '1990-11-13',
        'callback' => 20,
        'charset' => 'acucar',
        'consonant' => 'dcfg',
        'vowel' => 'aeiou',
        'alnum' => 'banana-123',
        'digit' => '120129 21212',
        'alpha' => 'banana',
        'containsArray' => ['www', 'banana', 'jfk', 'http'],
        'contains' => ['www', 'banana', 'jfk', 'http'],
        'countryCode' => 'BR',
        'creditCard' => '5555666677778884',
        'domain' => 'google.com.br',
        'directory' => __DIR__,
        'fileExists' => __FILE__,
        'endsWith' => 'pera banana',
        'equals' => 'banana',
        'even' => 8,
        'floatVal' => 9.8,
        'float' => 9.8,
        'graph' => 'LKM@#$%4;',
        'instance' => new DateTime,
        'int' => 9,
        'json' => '{"file":"laravel.php"}',
        'leapDate' => '1988-02-29',
        'leapYear' => '1988',
        'arrayVal' => ['Brazil'],
        'Arr' => ['Brazil'],
        'lowercase' => 'brazil',
        'macAddress' => '00:11:22:33:44:55',
        'multiple' => '9',
        'negative' => '-10',
        'noWhitespace' => 'laravelBrazil',
        'nullValue' => null,
        'numeric' => '179.9',
        'objectType' => new stdClass,
        'odd' => 3,
        'perfectSquare' => 25,
        'positive' => 1,
        'primeNumber' => 7,
        'punct' => '&,.;[]',
        'readable' => __FILE__,
        'regex' => '5',
        'roman' => 'VI',
        'slug' => 'laravel-brazil',
        'space' => '              b      ',
        'tld' => 'com',
        'uppercase' => 'BRAZIL',
        'version' => '1.0.0',
        'xdigit' => 'abc123',
        'writable' => __FILE__,
        'alwaysValid' => '@#$_',
        'boolType' => \is_int(2),
        'youtube' => 'http://youtu.be/l2gLWaGatFA',
        'vimeo' => 'http://vimeo.com/33677985',
        'video1' => 'https://youtu.be/l2gLWaGatFA',
        'video2' => 'https://vimeo.com/33677985',
        'email' => 'foo@google.com',
        'age' => '1990-11-13',
        'state' => 'SP',
    ];

    $validation = $this->validate($data, $rules);
    expect($validation->passes())->toBeTrue();
    expect($validation->errors())->toBeEmpty();
});

test('custom message', function () {
    $rules = ['number' => ['required', 'floatType']];
    $data = ['number' => 9];
    $messages = ['float_type' => 'The :attribute field must be a float.'];

    $validation = $this->validate($data, $rules, $messages);
    expect($validation->passes())->toBeFalse();
    expect($validation->errors())->toBeInstanceOf(MessageBag::class);
    expect($validation->errors()->first())->toEqual('The number field must be a float.');
});

test('laravel rule', function () {
    $validation = $this->validate([
        'distinct' => [
            ['id' => 20],
            ['id' => 21],
        ],
    ], [
        'distinct.*.id' => ['distinct'],
    ]);

    expect($validation->passes())->toBeTrue();

    $validation = $this->validate([
        'distinct' => [['id' => 20], ['id' => 20]],
    ], [
        'distinct.*.id' => ['distinct'],
    ]);

    expect($validation->passes())->toBeFalse();
});

test('invalid rule', function () {
    $this->expectException(BadMethodCallException::class);

    $validation = $this->validate([
        'age' => 20,
    ], [
        'age' => ['int', 'foobar:20'],
    ]);

    $validation->validate();
});

test('rule exception', function () {
    $this->expectException(ComponentException::class);
    $this->expectExceptionMessageMatches('*giggsey/libphonenumber-for-php*');

    $validation = $this->validate(['phone' => 'f'], ['phone' => 'phone:BR']);
    $validation->validate();
});

test('invalid values fail', function () {
    $validation = $this->validate([
        'cpf' => '11111111111',
        'cnpj' => '11111111111111',
        'even' => 3,
        'age' => '1950-01-01',
    ], [
        'cpf' => ['cpf'],
        'cnpj' => ['cnpj'],
        'even' => ['even'],
        'age' => ['minAge:18', 'maxAge:60'],
    ]);

    expect($validation->passes())->toBeFalse();
    expect($validation->errors()->keys())->toEqualCanonicalizing(['cpf', 'cnpj', 'even', 'age']);
});

test('extension takes precedence over respect rule', function () {
    Validator::extend('cpf', fn () => false, 'Custom CPF message.');

    $validation = $this->validate(['cpf' => '22205417118'], ['cpf' => 'cpf']);

    expect($validation->passes())->toBeFalse();
    expect($validation->errors()->first('cpf'))->toEqual('Custom CPF message.');
});

test('translated messages', function () {
    $validation = $this->validate(['cpf' => '1', 'age' => '1950-01-01'], ['cpf' => 'cpf', 'age' => 'maxAge:60']);

    expect($validation->errors()->first('cpf'))->toEqual('The cpf field must be a valid CPF.');
    expect($validation->errors()->first('age'))->toEqual('The age field must have a maximum age of 60 years.');

    $this->app->setLocale('pt_BR');

    $validation = $this->validate(['cpf' => '1'], ['cpf' => 'cpf']);

    expect($validation->errors()->first('cpf'))->toEqual('O campo cpf deve ser um CPF válido.');
});

test('translated messages with parameters', function () {
    $validation = $this->validate(
        ['number' => 10, 'word' => 'banana', 'code' => '1'],
        ['number' => 'betweenExclusive:1,10', 'word' => 'containsCount:a,2', 'code' => 'hetu'],
    );

    expect($validation->errors()->first('number'))->toEqual('The number field must be between 1 and 10 (exclusive).');
    expect($validation->errors()->first('word'))->toEqual('The word field must contain a exactly 2 times.');
    expect($validation->errors()->first('code'))->toEqual('The code field must be a valid Finnish personal identity code.');
});

test('application messages take precedence', function () {
    $this->app['translator']->addLines(['validation.cpf' => 'App CPF message.'], 'en');

    $validation = $this->validate(['cpf' => '1'], ['cpf' => 'cpf']);

    expect($validation->errors()->first('cpf'))->toEqual('App CPF message.');
});

test('max placeholder', function () {
    $validation = $this->validate(
        ['age' => '1950-01-01', 'name' => 'banana'],
        ['age' => 'maxAge:60', 'name' => 'length:1,3'],
        ['max_age' => ':max', 'length' => ':min-:max'],
    );

    expect($validation->errors()->first('age'))->toEqual('60');
    expect($validation->errors()->first('name'))->toEqual('1-3');
});

test('rule errors are not hidden', function () {
    $this->expectException(TypeError::class);

    $this->validate(['number' => 9], ['number' => 'multiple:abc'])->passes();
});

test('boolean parameters', function () {
    expect($this->validate(['domain' => 'example.notatld'], ['domain' => 'domain'])->passes())->toBeFalse();
    expect($this->validate(['domain' => 'example.notatld'], ['domain' => 'domain:true'])->passes())->toBeFalse();
    expect($this->validate(['domain' => 'example.notatld'], ['domain' => 'domain:false'])->passes())->toBeTrue();
});

test('invalid boolean parameters', function () {
    $this->validate(['domain' => 'example.notatld'], ['domain' => 'domain:abc'])->passes();
})->throws(InvalidArgumentException::class);

test('null parameters', function (string $rule) {
    expect($this->validate(['name' => 'abcde'], ['name' => $rule])->passes())->toBeTrue();
    expect($this->validate(['name' => 'abcdef'], ['name' => $rule])->passes())->toBeFalse();
})->with(['length:,5', 'length:null,5']);

test('array parameters', function () {
    expect($this->validate(['word' => 'banana'], ['word' => 'containsAny:nan,xyz'])->passes())->toBeTrue();
    expect($this->validate(['word' => 'banana'], ['word' => 'containsAny:xyz,abc'])->passes())->toBeFalse();
    expect($this->validate(['items' => ['a']], ['items' => 'subset:a,b'])->passes())->toBeTrue();
    expect($this->validate(['items' => ['c']], ['items' => 'subset:a,b'])->passes())->toBeFalse();

    $validation = $this->validate(['word' => 'x'], ['word' => 'containsAny:a,b']);

    expect($validation->errors()->first('word'))->toEqual('The word field must contain at least one of the following: a, b.');
});

test('values placeholder', function () {
    $validation = $this->validate(['word' => 'x'], ['word' => 'length:2,3'], ['length' => ':values']);

    expect($validation->errors()->first('word'))->toEqual('2, 3');
});

test('laravel rules keep their own placeholders', function () {
    $validation = $this->validate(['name' => 'a'], ['name' => 'min:3'], ['min' => ':min|:max']);

    expect($validation->errors()->first('name'))->toEqual('3|:max');
});

test('abstract rules are not available', function () {
    $this->validate(['age' => '2000-01-01'], ['age' => 'abstractAge:18'])->passes();
})->throws(BadMethodCallException::class);

test('implicit rules', function () {
    $validation = $this->validate(['empty' => '', 'blank' => '   '], [
        'empty' => 'notEmpty',
        'blank' => 'notBlank',
        'missing' => 'notOptional',
    ]);

    expect($validation->passes())->toBeFalse();
    expect($validation->errors()->keys())->toEqualCanonicalizing(['empty', 'blank', 'missing']);
});

test('rules requiring objects are not available', function () {
    $this->expectException(BadMethodCallException::class);

    $this->validate(['items' => [1, 2]], ['items' => 'each'])->passes();
});

test('related rules without inner rule', function () {
    expect($this->validate(['data' => ['name' => 'x']], ['data' => 'key:name'])->passes())->toBeTrue();
    expect($this->validate(['data' => ['age' => 1]], ['data' => 'key:name'])->passes())->toBeFalse();
});

test('package message for rules with a framework key', function () {
    $validation = $this->validate(['file' => 'abc'], ['file' => 'uploaded']);

    expect($validation->errors()->first('file'))->toEqual('The file field must be an uploaded file.');

    $validation = $this->validate(['file' => 'abc'], ['file' => 'uploaded'], ['file.uploaded' => 'Custom upload message.']);

    expect($validation->errors()->first('file'))->toEqual('Custom upload message.');
});

test('locales have the same keys and placeholders', function () {
    $en = require __DIR__.'/../lang/en/validation.php';
    $ptBr = require __DIR__.'/../lang/pt_BR/validation.php';

    expect(array_keys($ptBr))->toEqualCanonicalizing(array_keys($en));

    foreach ($en as $key => $message) {
        preg_match_all('/:\w+/', $message, $enPlaceholders);
        preg_match_all('/:\w+/', $ptBr[$key], $ptBrPlaceholders);

        expect($ptBrPlaceholders[0])->toEqualCanonicalizing($enPlaceholders[0], "Placeholders differ for [{$key}].");
    }
});

test('every available respect rule has a message', function () {
    $en = require __DIR__.'/../lang/en/validation.php';

    foreach (glob(__DIR__.'/../vendor/respect/validation/library/Rules/*.php') as $file) {
        $rule = basename($file, '.php');

        if (RuleFactory::exists($rule) && ! method_exists(BaseValidator::class, 'validate'.$rule)) {
            expect($en)->toHaveKey(Str::snake($rule));
        }
    }
});

test('length messages', function (string $rule, string $message) {
    $validation = $this->validate(['name' => 'abcdefghijklmnopqrstuvwxyz'], ['name' => $rule]);

    expect($validation->errors()->first('name'))->toEqual($message);
})->with([
    ['length:30,40', 'The name field must have a length between 30 and 40.'],
    ['length:30', 'The name field must have a length of at least 30.'],
    ['length:30,', 'The name field must have a length of at least 30.'],
    ['length:,5', 'The name field must have a length of at most 5.'],
    ['length:null,5', 'The name field must have a length of at most 5.'],
]);

test('length message replaced by the application', function () {
    $validation = $this->validate(['name' => 'abcdef'], ['name' => 'length:,5'], ['length' => ':min-:max']);

    expect($validation->errors()->first('name'))->toEqual('-5');
});
