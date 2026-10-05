<?php

namespace Datalogix\Validation\Tests;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Respect\Validation\Exceptions\ComponentException;

class ValidationTest extends TestCase
{
    public function test_common_rules()
    {
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
            'instance' => new \DateTime,
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
            'objectType' => new \stdClass,
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
        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors());
    }

    public function test_custom_message()
    {
        $rules = ['number' => ['required', 'floatType']];
        $data = ['number' => 9];
        $messages = ['float_type' => 'The :attribute field must be a float.'];

        $validation = $this->validate($data, $rules, $messages);
        $this->assertFalse($validation->passes());
        $this->assertInstanceOf(MessageBag::class, $validation->errors());
        $this->assertEquals('The number field must be a float.', $validation->errors()->first());
    }

    public function test_laravel_rule()
    {
        $validation = $this->validate([
            'distinct' => [
                ['id' => 20],
                ['id' => 21],
            ],
        ], [
            'distinct.*.id' => ['distinct'],
        ]);

        $this->assertTrue($validation->passes());

        $validation = $this->validate([
            'distinct' => [['id' => 20], ['id' => 20]],
        ], [
            'distinct.*.id' => ['distinct'],
        ]);

        $this->assertFalse($validation->passes());
    }

    public function test_invalid_rule(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $validation = $this->validate([
            'age' => 20,
        ], [
            'age' => ['int', 'foobar:20'],
        ]);

        $validation->validate();
    }

    public function test_rule_exception(): void
    {
        $this->expectException(ComponentException::class);
        $this->expectExceptionMessageMatches('*giggsey/libphonenumber-for-php*');

        $validation = $this->validate(['phone' => 'f'], ['phone' => 'phone:BR']);
        $validation->validate();
    }

    public function test_invalid_values_fail(): void
    {
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

        $this->assertFalse($validation->passes());
        $this->assertEqualsCanonicalizing(['cpf', 'cnpj', 'even', 'age'], $validation->errors()->keys());
    }

    public function test_extension_takes_precedence_over_respect_rule(): void
    {
        Validator::extend('cpf', fn () => false, 'Custom CPF message.');

        $validation = $this->validate(['cpf' => '22205417118'], ['cpf' => 'cpf']);

        $this->assertFalse($validation->passes());
        $this->assertEquals('Custom CPF message.', $validation->errors()->first('cpf'));
    }

    public function test_translated_messages(): void
    {
        $validation = $this->validate(['cpf' => '1', 'age' => '1950-01-01'], ['cpf' => 'cpf', 'age' => 'maxAge:60']);

        $this->assertEquals('The cpf must be a valid CPF.', $validation->errors()->first('cpf'));
        $this->assertEquals('The age must have a maximum age of 60 years.', $validation->errors()->first('age'));

        $this->app->setLocale('pt_BR');

        $validation = $this->validate(['cpf' => '1'], ['cpf' => 'cpf']);

        $this->assertEquals('O campo cpf deve ser um CPF válido.', $validation->errors()->first('cpf'));
    }

    public function test_translated_messages_with_parameters(): void
    {
        $validation = $this->validate(
            ['number' => 10, 'word' => 'banana', 'code' => '1'],
            ['number' => 'betweenExclusive:1,10', 'word' => 'containsCount:a,2', 'code' => 'hetu'],
        );

        $this->assertEquals('The number must be between 1 and 10 (exclusive).', $validation->errors()->first('number'));
        $this->assertEquals('The word must contain a exactly 2 times.', $validation->errors()->first('word'));
        $this->assertEquals('The code must be a valid Finnish personal identity code.', $validation->errors()->first('code'));
    }

    public function test_application_messages_take_precedence(): void
    {
        $this->app['translator']->addLines(['validation.cpf' => 'App CPF message.'], 'en');

        $validation = $this->validate(['cpf' => '1'], ['cpf' => 'cpf']);

        $this->assertEquals('App CPF message.', $validation->errors()->first('cpf'));
    }

    public function test_max_placeholder(): void
    {
        $validation = $this->validate(
            ['age' => '1950-01-01', 'name' => 'banana'],
            ['age' => 'maxAge:60', 'name' => 'length:1,3'],
            ['max_age' => ':max', 'length' => ':min-:max'],
        );

        $this->assertEquals('60', $validation->errors()->first('age'));
        $this->assertEquals('1-3', $validation->errors()->first('name'));
    }

    public function test_rule_errors_are_not_hidden(): void
    {
        $this->expectException(\TypeError::class);

        $this->validate(['number' => 9], ['number' => 'multiple:abc'])->passes();
    }

    public function test_boolean_parameters(): void
    {
        $this->assertFalse($this->validate(['domain' => 'example.notatld'], ['domain' => 'domain'])->passes());
        $this->assertFalse($this->validate(['domain' => 'example.notatld'], ['domain' => 'domain:true'])->passes());
        $this->assertTrue($this->validate(['domain' => 'example.notatld'], ['domain' => 'domain:false'])->passes());
    }

    public function test_implicit_rules(): void
    {
        $validation = $this->validate(['empty' => '', 'blank' => '   '], [
            'empty' => 'notEmpty',
            'blank' => 'notBlank',
            'missing' => 'notOptional',
        ]);

        $this->assertFalse($validation->passes());
        $this->assertEqualsCanonicalizing(['empty', 'blank', 'missing'], $validation->errors()->keys());
    }

    public function test_rules_requiring_objects_are_not_available(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $this->validate(['items' => [1, 2]], ['items' => 'each'])->passes();
    }

    public function test_related_rules_without_inner_rule(): void
    {
        $this->assertTrue($this->validate(['data' => ['name' => 'x']], ['data' => 'key:name'])->passes());
        $this->assertFalse($this->validate(['data' => ['age' => 1]], ['data' => 'key:name'])->passes());
    }
}
