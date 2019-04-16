<?php

namespace Lde\ApiHelper\Tests;

use Illuminate\Support\ServiceProvider;

class ApiHelperServiceProviderTest extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('apibuildertest', function() {
            return new ApiBuilderTest;
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
            __DIR__.'/Config/api_helper.php' => config_path('api_helper.php'),
        ]);
    }

     /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['apibuildertest'];
    }
}
