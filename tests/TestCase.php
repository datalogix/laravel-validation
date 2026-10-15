<?php

namespace Datalogix\Validation\Tests;

use Datalogix\Validation\ValidationServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            ValidationServiceProvider::class,
        ];
    }

    /**
     * @return Validator
     */
    protected function validate(array $data, array $rules, array $messages = [])
    {
        return $this->app->make('validator')->make($data, $rules, $messages);
    }
}
