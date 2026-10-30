<?php

namespace Datalogix\Validation;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator as BaseValidator;

final class Validator extends BaseValidator
{
    /**
     * The Respect rules that must run even when the attribute is empty.
     *
     * @var string[]
     */
    private const IMPLICIT_RULES = ['NotBlank', 'NotEmpty', 'NotOptional'];

    /**
     * The Respect rules whose message key also exists in Laravel's validation lines,
     * which would otherwise hide the package message.
     *
     * @var string[]
     */
    private const FRAMEWORK_KEY_RULES = ['Uploaded'];

    public function __construct(
        Translator $translator,
        array $data,
        array $rules,
        array $messages = [],
        array $attributes = [],
    ) {
        parent::__construct($translator, $data, $rules, $messages, $attributes);

        $this->implicitRules = \array_merge($this->implicitRules, self::IMPLICIT_RULES);
    }

    public function __call($method, $parameters)
    {
        $rule = \substr($method, 8);

        if (! \str_starts_with($method, 'validate')
            || isset($this->extensions[Str::snake($rule)])
            || ! RuleFactory::exists($rule)) {
            return parent::__call($method, $parameters);
        }

        return RuleFactory::make($rule, $parameters[2] ?? [])->validate($parameters[1] ?? null);
    }

    /**
     * Get the validation message for an attribute and rule.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string
     */
    protected function getMessage($attribute, $rule)
    {
        $message = parent::getMessage($attribute, $rule);
        $key = 'validation.'.Str::snake($rule);

        $isDefault = $message === $key
            || (\in_array($rule, self::FRAMEWORK_KEY_RULES, true) && $message === $this->translator->get($key));

        if ($isDefault && $this->isRespectRule($rule)) {
            $translated = $this->translator->get('laravel-validation::'.$key);

            if ($translated !== 'laravel-validation::'.$key) {
                return $translated;
            }
        }

        return $message;
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
        if (! $this->isRespectRule($rule)) {
            return parent::makeReplacements($message, $attribute, $rule, $parameters);
        }

        if ($rule === 'Length') {
            $message = $this->lengthMessage($message, $parameters);
        }

        $message = parent::makeReplacements($message, $attribute, $rule, $parameters);

        return \str_replace(
            [':values', ':value', ':min', ':max'],
            [\implode(', ', $parameters), $parameters[0] ?? '', $parameters[0] ?? '', $parameters[1] ?? $parameters[0] ?? ''],
            $message
        );
    }

    /**
     * Use the "at least" or "at most" message when the length rule has only one bound,
     * and the "greater than" or "less than" message when the bounds are exclusive,
     * unless the application replaced the package message.
     */
    private function lengthMessage(string $message, array $parameters): string
    {
        $hasMin = ! RuleFactory::isNullParameter($parameters[0] ?? null);
        $hasMax = ! RuleFactory::isNullParameter($parameters[1] ?? null);

        if ($message !== $this->translator->get('laravel-validation::validation.length') || (! $hasMin && ! $hasMax)) {
            return $message;
        }

        $key = 'validation.length'
            .($hasMin === $hasMax ? '' : ($hasMin ? '_min' : '_max'))
            .(\filter_var($parameters[2] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false ? '_exclusive' : '');

        $translated = $this->translator->get($key);

        return $translated !== $key ? $translated : $this->translator->get('laravel-validation::'.$key);
    }

    /**
     * Determine if the given rule is handled by Respect Validation.
     */
    private function isRespectRule(string $rule): bool
    {
        return ! \method_exists($this, 'validate'.$rule)
            && ! isset($this->extensions[Str::snake($rule)])
            && RuleFactory::exists($rule);
    }
}
