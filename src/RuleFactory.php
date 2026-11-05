<?php

namespace Datalogix\Validation;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use Respect\Validation\Rules\Length;
use Respect\Validation\Validatable;
use Stringable;

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
     * Convert the parameters to build a message: it never throws, because a failure
     * can be added by hand with any parameters, which are then used as given.
     *
     * @internal
     */
    public static function cast(string $rule, array $parameters): array
    {
        try {
            return self::castParameters(self::className($rule), $parameters);
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
        $constructorParameters = self::constructorParameters(self::className($rule));
        $parameters = self::cast($rule, $parameters);

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
     * Convert the parameters to the constructor types: "" and "null" to null for nullable
     * parameters, the FILTER_VALIDATE_BOOLEAN values to bool, numeric values to int,
     * and the remaining parameters to a single array for an array parameter, unless it is
     * already an array. Empty typed parameters and invalid values throw an exception, while
     * untyped parameters accept anything, because an empty value can be what they compare to.
     */
    private static function castParameters(string $class, array $parameters): array
    {
        foreach (self::constructorParameters($class) as $index => $parameter) {
            if (! \array_key_exists($index, $parameters)) {
                break;
            }

            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
            $value = $parameters[$index];

            if ($parameter->isVariadic()) {
                $values = \array_slice($parameters, $index);

                if ($type !== null && ! $type->allowsNull() && \array_filter($values, self::isEmptyParameter(...)) !== []) {
                    throw self::missingParameter($parameter->getName(), $class);
                }

                break;
            }

            if ($type?->allowsNull() && self::isNullParameter($value)) {
                $parameters[$index] = null;

                continue;
            }

            if ($type !== null && self::isEmptyParameter($value)) {
                throw self::missingParameter($parameter->getName(), $class);
            }

            if ($typeName === 'array') {
                $values = \is_array($value) ? $value : \array_slice($parameters, $index);

                $empty = \array_filter($values, self::isEmptyParameter(...));

                // A real array can hold an empty value, as long as it is not the only kind of value.
                if ($empty !== [] && (! \is_array($value) || \count($empty) === \count($values))) {
                    throw self::missingParameter($parameter->getName(), $class);
                }

                if (\is_array($value)) {
                    continue;
                }

                return [...\array_slice($parameters, 0, $index), $values];
            }

            if ($typeName === 'bool') {
                $parameters[$index] = self::castBoolean($value, $class);
            } elseif ($typeName === 'int') {
                $parameters[$index] = self::castInteger($value, $class);
            } elseif ($typeName !== null && ! self::matchesType($value, $typeName)) {
                throw new InvalidArgumentException('Invalid parameter ['.self::describe($value)."] for rule [{$class}].");
            }
        }

        return $parameters;
    }

    private static function castBoolean(mixed $value, string $class): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        $boolean = (\is_string($value) || \is_int($value)) && $value !== ''
            ? \filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        return $boolean ?? throw new InvalidArgumentException('Invalid boolean parameter ['.self::describe($value)."] for rule [{$class}].");
    }

    private static function castInteger(mixed $value, string $class): int
    {
        $number = \is_numeric($value) ? $value + 0 : null;

        if (\is_int($number)) {
            return $number;
        }

        if (\is_float($number) && \floor($number) === $number && \abs($number) < (float) PHP_INT_MAX) {
            return (int) $number;
        }

        throw new InvalidArgumentException('Invalid numeric parameter ['.self::describe($value)."] for rule [{$class}].");
    }

    private static function matchesType(mixed $value, string $type): bool
    {
        return match (true) {
            $type === 'string' => \is_scalar($value) || $value instanceof Stringable,
            $type === 'callable' => \is_callable($value),
            \class_exists($type) || \interface_exists($type) => $value instanceof $type,
            default => true,
        };
    }

    private static function missingParameter(string $name, string $class): InvalidArgumentException
    {
        return new InvalidArgumentException("Missing parameter [{$name}] for rule [{$class}].");
    }

    private static function describe(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : \get_debug_type($value);
    }

    private static function isEmptyParameter(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * Determine if the string parameter means "no value" for a nullable parameter.
     */
    private static function isNullParameter(mixed $value): bool
    {
        return self::isEmptyParameter($value) || (\is_string($value) && \strtolower($value) === 'null');
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
