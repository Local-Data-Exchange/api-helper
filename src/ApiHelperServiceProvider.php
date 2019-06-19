<?php

namespace Lde\ApiHelper;

use Illuminate\Support\ServiceProvider;

class ApiHelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('apibuilder', function () {
            return new ApiBuilder;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/Config/api_helper.php' => config_path('api_helper.php'),
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['apibuilder'];
    }
}
