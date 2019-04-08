<?php

namespace App\Events\ApiHelper;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiCallStarting
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $name;
    public $apiConfig;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($name, $config)
    {
        $this->name      = $name;
        $this->apiConfig = $config;
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }


}
