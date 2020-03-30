<?php

namespace Lde\ApiHelper;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // API Helper
        'Lde\ApiHelper\Events\ApiCallCompleted' => [
            'Lde\ApiHelper\Listeners\ApiCallCompletedListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        //
    }

    public function register(){

        $this->app->register(EventServiceProvider::class);
    }
}
