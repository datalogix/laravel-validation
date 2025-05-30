<?php

namespace Datalogix\Validation\Tests;

use Illuminate\Support\MessageBag;

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
            'isFile' => ['file'],
            'endsWith' => ['endsWith:banana'],
            'equals' => ['equals:banana'],
            'even' => ['even'],
            'floatVal' => ['floatVal'],
            'float' => ['floatVal'],
            'graph' => ['graph'],
            'instance' => ['instance:DateTime'],
            'int' => ['int'],
            'json' => ['json'],
            'leapDate' => ['leapDate:Y-m-d'],
            'leapYear' => ['leapYear'],
            'arrayVal' => ['arrayVal'],
            'Arr' => ['arrayVal'],
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
            'file' => __FILE__,
            'endsWith' => 'pera banana',
            'equals' => 'banana',
            'even' => 8,
            'floatVal' => 9.8,
            'graph' => 'LKM@#$%4;',
            'instance' => new \DateTime,
            'int' => 9,
            'json' => '{"file":"laravel.php"}',
            'leapDate' => '1988-02-29',
            'leapYear' => '1988',
            'arrayVal' => ['Brazil'],
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
        $this->expectException(\Respect\Validation\Exceptions\ComponentException::class);
        $this->expectExceptionMessageMatches('*giggsey/libphonenumber-for-php*');

        $validation = $this->validate(['phone' => 'f'], ['phone' => 'phone:BR']);
        $validation->validate();
    }
}
