<?php

namespace Lde\ApiHelper;

use Lde\ApiHelper\Events\ApiCallCompleted;
use Lde\ApiHelper\Events\ApiCallStarting;
use Lde\ApiHelper\Helpers\HelperException;
use Lde\ApiHelper\Helpers\ObfuscationHelper;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Auth;
use Spatie\ArrayToXml\ArrayToXml;

class ApiResponse extends Response
{

}