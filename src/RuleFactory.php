<?php

namespace Datalogix\Validation;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use Respect\Validation\Rules\Length;
use Respect\Validation\Validatable;
use Stringable;

class RuleFactory
{
    // Laravel normalizes the "int" and "bool" string rules to Integer and Boolean,
    // so the Int and Bool aliases are only reachable through RuleFactory::make().
    protected static array $alias = [
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
    protected static array $available = [];

    /**
     * @var array<string, \ReflectionParameter[]>
     */
    protected static array $constructorParameters = [];

    public static function exists(string $rule): bool
    {
        return static::$available[$rule] ??= $rule !== ''
            && \is_subclass_of($class = static::className($rule), Validatable::class)
            && (new ReflectionClass($class))->isInstantiable()
            && ! static::requiresObjects($class);
    }

    public static function make(string $rule, array $parameters = []): Validatable
    {
        $class = static::className($rule);

        $parameters = static::castParameters($class, $parameters);

        if ($class === Length::class && $parameters !== [] && ($parameters[0] ?? null) === null && ($parameters[1] ?? null) === null) {
            throw new InvalidArgumentException("The rule [{$class}] requires at least one bound.");
        }

        return new $class(...$parameters);
    }

    /**
     * Convert the parameters to build a message: it never throws, because a failure
     * can be added by hand with any parameters, which are then used as given.
     *
     * @internal
     */
    public static function cast(string $rule, array $parameters): array
    {
        try {
            return static::castParameters(static::className($rule), $parameters);
        } catch (InvalidArgumentException) {
            return $parameters;
        }
    }

    /**
     * Get the converted parameters as they appear in a message: the values of an array
     * parameter instead of the array and what follows it, without the parameters the
     * constructor ignores, and null for what cannot be shown.
     *
     * @internal
     */
    public static function messageParameters(string $rule, array $parameters): array
    {
        $constructorParameters = static::constructorParameters(static::className($rule));
        $parameters = static::cast($rule, $parameters);

        if (! \end($constructorParameters) || ! \end($constructorParameters)->isVariadic()) {
            $parameters = \array_slice($parameters, 0, \count($constructorParameters));
        }

        $values = [];

        foreach ($parameters as $value) {
            if (\is_array($value)) {
                $values = [...$values, ...Arr::flatten($value)];

                break;
            }

            $values[] = $value;
        }

        return \array_map(static fn ($value) => match (true) {
            \is_bool($value) => $value ? 'true' : 'false',
            \is_scalar($value), $value instanceof Stringable => (string) $value,
            default => null,
        }, $values);
    }

    /**
     * Determine if the rule constructor requires objects (e.g. other rules),
     * which cannot be given through a string rule like "each:...".
     */
    protected static function requiresObjects(string $class): bool
    {
        foreach (static::constructorParameters($class) as $parameter) {
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
     * Convert the parameters to the constructor types: "" and "null" to null for nullable
     * parameters, the FILTER_VALIDATE_BOOLEAN values to bool, numeric values to int,
     * and the remaining parameters to a single array for an array parameter, unless it is
     * already an array. Empty typed parameters and invalid values throw an exception, while
     * untyped parameters accept anything, because an empty value can be what they compare to.
     */
    protected static function castParameters(string $class, array $parameters): array
    {
        foreach (static::constructorParameters($class) as $index => $parameter) {
            if (! \array_key_exists($index, $parameters)) {
                break;
            }

            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
            $value = $parameters[$index];

            if ($parameter->isVariadic()) {
                $values = \array_slice($parameters, $index);

                if ($type !== null && ! $type->allowsNull() && \array_filter($values, static::isEmptyParameter(...)) !== []) {
                    throw static::missingParameter($parameter->getName(), $class);
                }

                break;
            }

            if ($type?->allowsNull() && static::isNullParameter($value)) {
                $parameters[$index] = null;

                continue;
            }

            if ($type !== null && static::isEmptyParameter($value)) {
                throw static::missingParameter($parameter->getName(), $class);
            }

            if ($typeName === 'array') {
                $values = \is_array($value) ? $value : \array_slice($parameters, $index);

                $empty = \array_filter($values, static::isEmptyParameter(...));

                // A real array can hold an empty value, as long as it is not the only kind of value.
                if ($empty !== [] && (! \is_array($value) || \count($empty) === \count($values))) {
                    throw static::missingParameter($parameter->getName(), $class);
                }

                if (\is_array($value)) {
                    continue;
                }

                return [...\array_slice($parameters, 0, $index), $values];
            }

            if ($typeName === 'bool') {
                $parameters[$index] = static::castBoolean($value, $class);
            } elseif ($typeName === 'int') {
                $parameters[$index] = static::castInteger($value, $class);
            } elseif ($typeName !== null && ! static::matchesType($value, $typeName)) {
                throw new InvalidArgumentException('Invalid parameter ['.static::describe($value)."] for rule [{$class}].");
            }
        }

        return $parameters;
    }

    protected static function castBoolean(mixed $value, string $class): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        $boolean = (\is_string($value) || \is_int($value)) && $value !== ''
            ? \filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        return $boolean ?? throw new InvalidArgumentException('Invalid boolean parameter ['.static::describe($value)."] for rule [{$class}].");
    }

    protected static function castInteger(mixed $value, string $class): int
    {
        $number = \is_numeric($value) ? $value + 0 : null;

        if (\is_int($number)) {
            return $number;
        }

        if (\is_float($number) && \floor($number) === $number && \abs($number) < (float) PHP_INT_MAX) {
            return (int) $number;
        }

        throw new InvalidArgumentException('Invalid numeric parameter ['.static::describe($value)."] for rule [{$class}].");
    }

    protected static function matchesType(mixed $value, string $type): bool
    {
        return match (true) {
            $type === 'string' => \is_scalar($value) || $value instanceof Stringable,
            $type === 'callable' => \is_callable($value),
            \class_exists($type) || \interface_exists($type) => $value instanceof $type,
            default => true,
        };
    }

    protected static function missingParameter(string $name, string $class): InvalidArgumentException
    {
        return new InvalidArgumentException("Missing parameter [{$name}] for rule [{$class}].");
    }

    protected static function describe(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : \get_debug_type($value);
    }

    protected static function isEmptyParameter(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * Determine if the string parameter means "no value" for a nullable parameter.
     */
    protected static function isNullParameter(mixed $value): bool
    {
        return static::isEmptyParameter($value) || (\is_string($value) && \strtolower($value) === 'null');
    }

    /**
     * @return \ReflectionParameter[]
     */
    protected static function constructorParameters(string $class): array
    {
        return static::$constructorParameters[$class] ??= (new ReflectionClass($class))->getConstructor()?->getParameters() ?? [];
    }

    protected static function className(string $rule): string
    {
        return 'Respect\\Validation\\Rules\\'.(static::$alias[$rule] ?? $rule);
    }
}
