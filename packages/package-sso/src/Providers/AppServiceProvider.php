<?php

declare(strict_types=1);

namespace Fisher\SSO\Providers;

use App\Support\PackageHandler;
use Fisher\SSO\Services\OAGuard;
use Fisher\SSO\Services\OAUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Boorstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        // Register translations.
        $this->loadTranslationsFrom($this->app->make('path.package-sso.lang'), 'package-sso');

        // Publish config.
        $this->publishes([
            $this->app->make('path.package-sso.config') . '/sso.php' => $this->app->configPath('sso.php'),
        ], 'package-sso-config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Bind all of the package paths in the container.
        $this->bindPathsInContainer();

        // register cntainer aliases
        $this->registerCoreContainerAliases();

        // Register singletons.
        $this->registerSingletions();

        // Register package handlers.
        $this->registerPackageHandlers();

        $this->registerSsoService();
    }

    /**
     * register sso service.
     *
     * @return void
     */
    protected function registerSsoService()
    {
        Auth::provider('oa', function () {
            return new OAUserProvider();
        });

        Auth::extend('oa', function ($app, $name, array $config) {
            return new OAGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });
    }

    /**
     * Bind paths in container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        foreach ([
                     'path.package-sso' => $root = dirname(dirname(__DIR__)),
                     'path.package-sso.config' => $root . '/config',
                     'path.package-sso.resources' => $resources = $root . '/resources',
                     'path.package-sso.lang' => $resources . '/lang',
                 ] as $abstract => $instance) {
            $this->app->instance($abstract, $instance);
        }
    }

    /**
     * Register singletons.
     *
     * @return void
     */
    protected function registerSingletions()
    {
        // Owner handler.
        $this->app->singleton('package-sso:handler', function () {
            return new \Fisher\SSO\Handlers\PackageHandler();
        });

        // Develop handler.
        $this->app->singleton('package-sso:dev-handler', function ($app) {
            return new \Fisher\SSO\Handlers\DevPackageHandler($app);
        });
    }

    /**
     * Register the package class aliases in the container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases()
    {
        foreach ([
                     'package-sso:handler' => [
                         \Fisher\SSO\Handlers\PackageHandler::class,
                     ],
                     'package-sso:dev-handler' => [
                         \Fisher\SSO\Handlers\DevPackageHandler::class,
                     ],
                 ] as $abstract => $aliases) {
            foreach ($aliases as $alias) {
                $this->app->alias($abstract, $alias);
            }
        }
    }

    /**
     * Register package handlers.
     *
     * @return void
     */
    protected function registerPackageHandlers()
    {
        $this->loadHandleFrom('package-sso', 'package-sso:handler');
        $this->loadHandleFrom('package-sso-dev', 'package-sso:dev-handler');
    }

    /**
     * Register handler.
     *
     * @param string $name
     * @param \App\Support\PackageHandler|string $handler
     * @return void
     */
    private function loadHandleFrom(string $name, $handler)
    {
        PackageHandler::loadHandleFrom($name, $handler);
    }
}
