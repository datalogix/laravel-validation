<?php

namespace Datalogix\Validation;

use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['validator']->resolver(static function ($translator, $data, $rules, $messages, $customAttributes) {
            return new Validator($translator, $data, $rules, $messages, $customAttributes);
        });
    }
}
