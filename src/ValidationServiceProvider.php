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
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laravel-validation');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/laravel-validation'),
            ], 'laravel-validation-lang');
        }

        $this->callAfterResolving('validator', static function ($factory) {
            $factory->resolver(static fn ($translator, $data, $rules, $messages, $attributes) => new Validator(
                $translator, $data, $rules, $messages, $attributes
            ));
        });
    }
}
