<?php

namespace Lde\ApiHelper;

use GuzzleHttp\Psr7\Response;

class ApiResponse extends Response
{
    public $success = false;
    public $body = null;
    public $meta = null;
    public $error = null;
}
