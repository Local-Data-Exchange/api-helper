<?php

namespace Lde\ApiHelper\Listeners;

use Lde\ApiHelper\Helpers\StatsHelper;
use Lde\ApiHelper\Events\ApiCallCompleted;

class ApiCallCompletedListener
{
    /**
     * Handle user login events.
     */
    public function handle(ApiCallCompleted $event)
    {
        $connectionConfig = config('api_helper.connections.' . $event->connection);

        if(config('api_helper.log_stats')) {
            // log stat to Prometheus       
            StatsHelper::incCounter($connectionConfig['counter_name'], 1, $connectionConfig['counter_description']);
        }
    }
}
