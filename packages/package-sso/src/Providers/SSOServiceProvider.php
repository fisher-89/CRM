<?php

namespace Fisher\SSO\Providers;

use Illuminate\Support\ServiceProvider;
use Fisher\SSO\Services\RequestSSOService;

class SSOServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('api', function ($app) {
        	return new RequestSSOService($app->make('request'));
        });
    }
}
