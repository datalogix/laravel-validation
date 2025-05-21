<?php

namespace Datalogix\Validation;

use Illuminate\Validation\Validator as BaseValidator;
use Respect\Validation\Exceptions\ComponentException;

final class Validator extends BaseValidator
{
    public function __call($method, $parameters)
    {
        try {
            $rule = \mb_substr($method, 8);
            [$value, $args] = [$parameters[1], $parameters[2]];

            return RuleFactory::make($rule, $args)->validate($value);
        } catch (ComponentException $e) {
            throw $e;
        } catch (\Exception $e) {
            return parent::__call($method, $parameters);
        }
    }

    /**
     * Replace all error message place-holders with actual values.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array  $parameters
     * @return string
     */
    public function makeReplacements($message, $attribute, $rule, $parameters)
    {
        $message = parent::makeReplacements($message, $attribute, $rule, $parameters);

        return str_replace(
            [':values', ':value', ':min', ':max'],
            [implode(', ', $parameters), $parameters[0] ?? '', $parameters[0] ?? '', $parameters[1] ?? ''],
            $message
        );
    }
}
