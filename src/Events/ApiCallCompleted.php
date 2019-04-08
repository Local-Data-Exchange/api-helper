<?php

namespace Lde\ApiHelper\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiCallCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $name;
    public $response;
    public $apiConfig;
    public $duration;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($name, $response, $config, $duration)
    {
        $this->name      = $name;
        $this->response  = $response;
        $this->apiConfig = $config;
        $this->duration  = $duration;
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
