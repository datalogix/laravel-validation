<?php

namespace Datalogix\Validation\Tests;

use Datalogix\Validation\ValidationServiceProvider;
use GrahamCampbell\TestBench\AbstractPackageTestCase;

abstract class TestCase extends AbstractPackageTestCase
{
    /**
     * Get the service provider class.
     */
    protected static function getServiceProviderClass(): string
    {
        return ValidationServiceProvider::class;
    }

    /**
     * @return \Illuminate\Validation\Validator
     */
    protected function validate(array $data, array $rules, array $messages = [])
    {
        return $this->app->make('validator')->make($data, $rules, $messages);
    }
}
