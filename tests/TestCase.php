<?php

namespace Datalogix\Validation\Tests;

use Datalogix\Validation\ValidationServiceProvider;
use GrahamCampbell\TestBench\AbstractPackageTestCase;

abstract class TestCase extends AbstractPackageTestCase
{
    /**
     * Get the service provider class.
     *
     * @return string
     */
    protected static function getServiceProviderClass(): string
    {
        return ValidationServiceProvider::class;
    }

    /**
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @return \Illuminate\Validation\Validator
     */
    protected function validate(array $data, array $rules, array $messages = [])
    {
        return $this->app->make('validator')->make($data, $rules, $messages);
    }
}
