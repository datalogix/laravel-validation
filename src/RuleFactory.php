<?php

namespace Datalogix\Validation;

use ReflectionClass;
use ReflectionNamedType;
use Respect\Validation\Validatable;

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
        'Object' => 'ObjectType',
        'ObjectVal' => 'ObjectType',
        'Prnt' => 'Printable',
        'True' => 'TrueVal',
    ];

    /**
     * @var array<string, bool>
     */
    private static $exists = [];

    /**
     * @var array<string, \ReflectionParameter[]>
     */
    private static $parameters = [];

    public static function exists(string $rule): bool
    {
        return self::$exists[$rule] ??= $rule !== ''
            && ! \str_starts_with($rule, 'Abstract')
            && \is_subclass_of($class = self::className($rule), Validatable::class)
            && ! self::requiresObjects($class);
    }

    public static function make(string $rule, array $parameters = []): Validatable
    {
        $class = self::className($rule);

        return new $class(...self::castParameters($class, $parameters));
    }

    /**
     * Determine if the rule constructor requires objects (e.g. other rules),
     * which cannot be given through a string rule like "each:...".
     */
    private static function requiresObjects(string $class): bool
    {
        foreach (self::constructorParameters($class) as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
                && ($parameter->isVariadic() || ! $parameter->isOptional())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert "true" and "false" strings for constructor parameters typed as bool.
     */
    private static function castParameters(string $class, array $parameters): array
    {
        foreach (self::constructorParameters($class) as $index => $parameter) {
            $type = $parameter->getType();

            if ($parameter->isVariadic() || ! isset($parameters[$index])) {
                break;
            }

            if ($type instanceof ReflectionNamedType
                && $type->getName() === 'bool'
                && \is_string($parameters[$index])) {
                $parameters[$index] = \filter_var($parameters[$index], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $parameters;
    }

    /**
     * @return \ReflectionParameter[]
     */
    private static function constructorParameters(string $class): array
    {
        return self::$parameters[$class] ??= (new ReflectionClass($class))->getConstructor()?->getParameters() ?? [];
    }

    private static function className(string $rule): string
    {
        return 'Respect\\Validation\\Rules\\'.(self::$alias[$rule] ?? $rule);
    }
}
