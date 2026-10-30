<?php

namespace Datalogix\Validation;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use Respect\Validation\Validatable;

final class RuleFactory
{
    // Laravel normalizes the "int" and "bool" string rules to Integer and Boolean,
    // so the Int and Bool aliases are only reachable through RuleFactory::make().
    private static array $alias = [
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
    private static array $available = [];

    /**
     * @var array<string, \ReflectionParameter[]>
     */
    private static array $constructorParameters = [];

    public static function exists(string $rule): bool
    {
        return self::$available[$rule] ??= $rule !== ''
            && \is_subclass_of($class = self::className($rule), Validatable::class)
            && (new ReflectionClass($class))->isInstantiable()
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
     * Convert the string parameters to the constructor types: "" and "null" to null
     * for nullable parameters, "true" and "false" to bool, and the remaining
     * parameters to a single array for an array parameter.
     */
    private static function castParameters(string $class, array $parameters): array
    {
        foreach (self::constructorParameters($class) as $index => $parameter) {
            $type = $parameter->getType();

            if ($parameter->isVariadic() || ! isset($parameters[$index])) {
                break;
            }

            if ($type instanceof ReflectionNamedType && $type->getName() === 'array') {
                return [...\array_slice($parameters, 0, $index), \array_slice($parameters, $index)];
            }

            if ($type?->allowsNull() && \in_array($parameters[$index], ['', 'null'], true)) {
                $parameters[$index] = null;

                continue;
            }

            if ($type instanceof ReflectionNamedType
                && $type->getName() === 'bool'
                && \is_string($parameters[$index])) {
                $value = \filter_var($parameters[$index], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($value === null) {
                    throw new InvalidArgumentException("Invalid boolean parameter [{$parameters[$index]}] for rule [{$class}].");
                }

                $parameters[$index] = $value;
            }
        }

        return $parameters;
    }

    /**
     * @return \ReflectionParameter[]
     */
    private static function constructorParameters(string $class): array
    {
        return self::$constructorParameters[$class] ??= (new ReflectionClass($class))->getConstructor()?->getParameters() ?? [];
    }

    private static function className(string $rule): string
    {
        return 'Respect\\Validation\\Rules\\'.(self::$alias[$rule] ?? $rule);
    }
}
