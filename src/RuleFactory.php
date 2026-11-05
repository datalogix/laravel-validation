<?php

namespace Datalogix\Validation;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use Respect\Validation\Rules\Length;
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

        $parameters = self::castParameters($class, $parameters);

        if ($class === Length::class && $parameters !== [] && ($parameters[0] ?? null) === null && ($parameters[1] ?? null) === null) {
            throw new InvalidArgumentException("The rule [{$class}] requires at least one bound.");
        }

        return new $class(...$parameters);
    }

    /**
     * Convert the parameters as the rule constructor receives them.
     *
     * @internal
     */
    public static function cast(string $rule, array $parameters): array
    {
        return self::castParameters(self::className($rule), $parameters);
    }

    /**
     * Get the parameters to show in a message: flat, and with null for skipped nullable parameters.
     *
     * @internal
     */
    public static function messageParameters(string $rule, array $parameters): array
    {
        $constructorParameters = self::constructorParameters(self::className($rule));

        foreach ($parameters as $index => $value) {
            if (($constructorParameters[$index] ?? null)?->getType()?->allowsNull() && self::isNullParameter($value)) {
                $parameters[$index] = null;
            }
        }

        return Arr::flatten($parameters);
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
     * parameters to a single array for an array parameter, unless it is already an array.
     * Numeric parameters are only checked, so they reach the constructor as given.
     */
    private static function castParameters(string $class, array $parameters): array
    {
        foreach (self::constructorParameters($class) as $index => $parameter) {
            $type = $parameter->getType();

            if (! \array_key_exists($index, $parameters)) {
                break;
            }

            if ($type?->allowsNull() && self::isNullParameter($parameters[$index])) {
                $parameters[$index] = null;

                continue;
            }

            if ($type !== null && $parameters[$index] === null) {
                throw new InvalidArgumentException("Missing parameter [{$parameter->getName()}] for rule [{$class}].");
            }

            if ($parameter->isVariadic()) {
                break;
            }

            if ($type instanceof ReflectionNamedType && $type->getName() === 'array') {
                if (\is_array($parameters[$index])) {
                    continue;
                }

                return [...\array_slice($parameters, 0, $index), \array_slice($parameters, $index)];
            }

            if ($type instanceof ReflectionNamedType
                && $type->getName() === 'bool'
                && \is_string($parameters[$index])) {
                $value = $parameters[$index] === ''
                    ? null
                    : \filter_var($parameters[$index], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($value === null) {
                    throw new InvalidArgumentException("Invalid boolean parameter [{$parameters[$index]}] for rule [{$class}].");
                }

                $parameters[$index] = $value;
            }

            if ($type instanceof ReflectionNamedType
                && \in_array($type->getName(), ['int', 'float'], true)
                && \is_string($parameters[$index])
                && ! self::isNumericParameter($parameters[$index], $type->getName())) {
                throw new InvalidArgumentException("Invalid numeric parameter [{$parameters[$index]}] for rule [{$class}].");
            }
        }

        return $parameters;
    }

    /**
     * Determine if the string parameter can be given to an int or float constructor parameter.
     */
    private static function isNumericParameter(string $value, string $type): bool
    {
        return \is_numeric($value) && ($type === 'float' || (float) (int) $value === (float) $value);
    }

    /**
     * Determine if the string parameter means "no value" for a nullable parameter.
     */
    private static function isNullParameter(mixed $value): bool
    {
        return $value === null || $value === '' || (\is_string($value) && \strtolower($value) === 'null');
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
