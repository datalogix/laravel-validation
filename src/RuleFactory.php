<?php

namespace Datalogix\Validation;

use ReflectionClass;

final class RuleFactory
{
    private static $alias = [
        'Arr' => 'ArrayVal',
        'Bool' => 'BoolType',
        'Cntrl' => 'Control',
        'False' => 'FalseVal',
        'FileExists' => 'Exists',
        'Float' => 'FloatVal',
        'Int' => 'IntVal',
        'Iterable' => 'IterableType',
        'IterableVal' => 'IterableType',
        'MinimumAge' => 'MinAge',
        'NullValue' => 'NullType',
        'Numeric' => 'NumericVal',
        'Object' => 'ObjectType',
        'ObjectVal' => 'ObjectType',
        'Prnt' => 'Printable',
        'String' => 'StringType',
        'True' => 'TrueVal',
    ];

    public static function make(string $rule, array $parameters = [])
    {
        $validator = new ReflectionClass(
            'Respect\\Validation\\Rules\\'.(self::$alias[$rule] ?? $rule)
        );

        return $validator->newInstanceArgs($parameters);
    }
}
