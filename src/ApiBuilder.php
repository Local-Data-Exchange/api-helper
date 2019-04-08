<?php

namespace ProPack\ApiHelper;

use ProPack\ApiHelper\HelperException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiBuilder
{
    protected $apiName;

    protected $useBillingLogger = false;

    protected $useStatsLogger = false;

    public $requestOptions = [];

    /**
     * ApiHelper constructor.
     *
     */
    public function __construct()
    {
        // default params
        $this->requestOptions = config('api_helper.default_request_options', []);
    }

    /**
     * Host method define the name of project that we are going to use for api
     * @param $apiName 
     * @param $params[] -- Pass additional headers name and value in array
     */
    public function host($apiName,$params = [])
    {
        $object = new  ApiBuilder();
        $this->apiName = $apiName;
        $object->apiname = $this->apiName;
        if(!empty($params))
        {
            foreach ($params as $headers) {
                $this->addDefaultHeader($headers['name'], $headers['value']);
            }
        }
        $object->headers = $this->requestOptions['headers'];
        return $object;

    }
    
    /**
     * Add header to default config
     *
     * @param $name
     * @param $value
     */
    public function addDefaultHeader($name, $value)
    {
        // Add header to requestOptions
        $this->requestOptions['headers'][$name] = $value;
    }

    /**
     * @param string $apiName
     */
    public function setApiName($apiName)
    {
        $this->apiName = $apiName;
    }

    /**
     * @param $method
     * @param $uri
     * @param $params
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function call($method, $uri, $params = [])
    {
        // Start the timer
        $startTime = microtime(true);

        $tries = 0;
        $success = false;
        $object = new ApiBuilder();

        while ($success == false && $tries <= config('api_helper.retries', 3)) 
        {
            $tries++;

            $client = new Client();
            try {

                // Merge params
                $params = array_merge($this->requestOptions, $params);

                // Send request
                $response = $client->request($method, $uri, $params);

                // If we got this far, we have a response.
                // TODO:: we assume JSON here - should we?
                $data = json_decode($response->getBody(), true);
                //dd($response);

                debug('ApiHelper->call() - Call succeeded', [
                    'api_name' => $this->apiName,
                    'method' => $method,
                    'uri' => $uri,
                    'params' => $params,
                    'response' => $data,
                    'tries' => $tries,
                ]);

                // Billing
                if ($this->useBillingLogger) {
                    BillingHelper::log("ApiHelper [{$this->apiName}][$method]", 1, strlen(json_encode($params)), strlen(json_encode($data)), microtime(true) - $startTime, ['success' => true]);
                }

                // log stat to Prometheus
                if ($this->useStatsLogger) {
                    \App\Helpers\StatsHelper::incCounter('external_api_calls_total', 1, [
                        $this->apiName,
                        $method,
                        $response->getStatusCode(),
                    ], "Total number of external API calls processed.", [
                        'name',
                        'method',
                        'status',
                    ]);

                    // Add histogram to Prometheus
                    \App\Helpers\StatsHelper::incHistogram('external_apis_response_time_seconds', (float) (microtime(true) - $startTime), [$this->apiName, $method, strtoupper($method), 'success'], "Response time for external API calls.", ['provider', 'method', 'request_type', 'status']);
                }

                $object->success = true;
                $object->data = $data;
                $object->meta->method = $method;
                $object->meta->uri = $uri;
                $object->meta->params = $params;
                $object->meta->status_code = $status_code;
                return $object;
            } catch (RequestException $ex) {

                $httpStatusCode = $ex->hasResponse() && $ex->getResponse() ? $ex->getResponse()->getStatusCode() : 500;
                $httpStatus = $ex->hasResponse() && $ex->getResponse() ? $ex->getResponse()->getReasonPhrase() : '';
                $httpBody = $ex->hasResponse() && $ex->getResponse() ? $ex->getResponse()->getBody()->getContents() : '';

                info("ApiHelper threw a RequestException", [
                    'api_name' => $this->apiName,
                    'method' => $method,
                    'uri' => $uri,
                    'params' => $params,
                    'error' => $ex->getMessage(),
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'http_status_code' => $httpStatusCode,
                    'http_status' => $httpStatus,
                    'tries' => $tries,
                ]);

                // Billing
                if ($this->useBillingLogger) {
                    BillingHelper::log("ApiHelper [{$this->apiName}][$method]", 1, strlen(json_encode($params)), 0, microtime(true) - $startTime, [
                        'success' => false,
                        'http_status_code' => $httpStatusCode,
                        'http_status' => $httpStatus,
                    ]);
                }

                // log stat to Prometheus
                if ($this->useStatsLogger) {
                    \App\Helpers\StatsHelper::incCounter('external_api_calls_total', 1, [
                        $this->apiName,
                        $method,
                        $httpStatusCode,
                    ], "Total number of external API calls processed.", [
                        'name',
                        'method',
                        'status',
                    ]);

                    // Add histogram to Prometheus
                    \App\Helpers\StatsHelper::incHistogram('external_apis_response_time_seconds', (float) (microtime(true) - $startTime), [$this->apiName, $method, strtoupper($method), 'request_exception'], "Response time for external API calls.", ['provider', 'method', 'request_type', 'status']);
                }

                // unset $client
                unset($client);

                $object->success = false;
                $object->error = $ex->getMessage();
                $object->meta->method = $method;
                $object->meta->uri = $uri;
                $object->meta->params = $params;
                $object->meta->status_code = $status_code;

                // Check if we should retry
                $statusesNotToRetry = [400, 401, 404, 406, 422];

                if (in_array($httpStatusCode, $statusesNotToRetry)) {
                    debug('ApiHelper->call() - Call failed but status is in blacklist. Not retrying.', [
                        'api_name' => $this->apiName,
                        'method' => $method,
                        'uri' => $uri,
                        'tries' => $tries,
                        'http_status_code' => $httpStatusCode,
                        'http_status' => $httpStatus,
                    ]);

                    return $object;
                }
            } catch (\Exception $ex) {

                $httpStatusCode = 500;
                $httpStatus = $ex->getMessage();

                info("ApiHelper threw an Exception", ExceptionHelper::toArray($ex, [
                    'api_name' => $this->apiName,
                    'method' => $method,
                    'uri' => $uri,
                    'params' => $params,
                    'tries' => $tries,
                ]));

                // Billing
                if ($this->useBillingLogger) {
                    BillingHelper::log("ApiHelper [{$this->apiName}][$method]", 1, strlen(json_encode($params)), 0, microtime(true) - $startTime, [
                        'success' => false,
                        'http_status_code' => $httpStatusCode,
                        'http_status' => $httpStatus,
                    ]);
                }

                // log stat to Prometheus
                if ($this->useStatsLogger) {
                    \App\Helpers\StatsHelper::incCounter('external_api_calls_total', 1, [
                        $this->apiName,
                        $method,
                        $httpStatusCode,
                    ], "Total number of external API calls processed.", [
                        'name',
                        'method',
                        'status',
                    ]);

                    // Add histogram to Prometheus
                    \App\Helpers\StatsHelper::incHistogram('external_apis_response_time_seconds', (float) (microtime(true) - $startTime), [$this->apiName, $method, strtoupper($method), 'request_exception'], "Response time for external API calls.", ['provider', 'method', 'request_type', 'status']);
                }

                // unset $client
                unset($client);

                $object->success = false;
                $object->error = $ex->getMessage();
                $object->meta->method = $method;
                $object->meta->uri = $uri;
                $object->meta->params = $params;
                $object->meta->status_code = $status_code;
            }
        }

        // We got here, this means we ran out of retries
        info("ApiHelper '{$method}' had a fatal failure. No more retries. Giving up.", [
            'api_name' => $this->apiName,
            'method' => $method,
            'uri' => $uri,
            'params' => $params,
            'error' => $object->error,
            'tries' => $tries,
        ]);

        return $object;
    }

    /**
     * @param $method
     * @param $uri
     * @param $params
     *
     * @link http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function callAsync($method, $uri, $params = []): \GuzzleHttp\Promise\PromiseInterface
    {
        // Start the timer
        $startTime = microtime(true);

        try {
            $client = new Client();

            // Merge params
            $params = array_merge($this->requestOptions, $params);

            // Create Promise
            return $client->requestAsync($method, $uri, $params);

        } catch (\Exception $ex) {
            return null;
        }

    }

    /**
     * Magic method to call api
     *
     * Call API using name provided in settings, eg $api->get_users($data)
     *
     * @param $name
     * @param $arguments
     *
     * @return array|\GuzzleHttp\Promise\PromiseInterface
     * @throws \App\Exceptions\HelperException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __call($name, $arguments)
    {
        // Set BillingLogger
        $this->useBillingLogger = config('api_helper.use_billing_logger', true);

        // Set StatsLogger
        $this->useStatsLogger = config('api_helper.use_stats_logger', false);
        $api = null;

        if(config('api_helper.apis.'.$this->apiName.'.'.$name)){
            $api = config('api_helper.apis.'.$this->apiName.'.'.$name);
        } elseif(config('api_helper.apis.' . $name)) {
            $api = config('api_helper.apis.' . $name);
        }

        if ($api) {
            // Set API Name
            // $this->setApiName(array_get($api, 'name', config('api_helper.default_name', 'UNKNOWN')));

            // Method
            $method = array_get($api, 'method', 'GET');

            // Uri
            if (!$uri = array_get($api, 'uri')) {
                throw new HelperException("Uri is not configured for {$name} API!");
            }

            // Path mappings
            $uri = $this->processPathMappings($arguments, $api, $uri);

            // Query mappings
            $uri = $this->processQueryMappings($arguments, $api, $uri);
            //var_dump($uri);

            // JSON mappings
            $json = $this->processJsonMappings($arguments, $api);
            //dd($json);
            //var_dump(json_encode($json));
            // dd($arguments[0]);
            if (array_get($arguments[0], 'async', false) == true) {
                // Call the API
                return $this->callAsync($method, $uri, ['json' => $json]);

            } else {
                // Call the API
                return $this->call($method, $uri, ['json' => $json]);
            }

        } else {
            throw new HelperException("Api {$name} is not configured!");
        }
    }

    /**
     * @param $arguments
     * @param $api
     * @param $uri
     *
     * @return string
     */
    protected function processPathMappings($arguments, $api, $uri): string
    {
        foreach (array_get($api, 'mappings.path', []) as $key => $value) {
            $uri = str_ireplace('{' . $key . '}', array_get($arguments[0], $value, 'UNKNOWN'), $uri);
        }

        return $uri;
    }

    /**
     * @param $arguments
     * @param $api
     * @param $uri
     *
     * @return string
     */
    protected function processQueryMappings($arguments, $api, $uri): string
    {
        $query = [];
        foreach (array_get($api, 'mappings.query', []) as $key => $value) {
            $query[$key] = array_get($arguments[0], $value, '');
        }

        if (count($query) > 0) {
            $uri .= strrpos($uri, '?') ? '&' : '?';
            $uri .= http_build_query($query);
        }

        return $uri;
    }

    /**
     * @param $arguments
     * @param $api
     *
     * @return array
     */
    protected function processJsonMappings($arguments, $api): array
    {
        $json = json_encode(array_get($api, 'json', []));
        foreach (array_get($api, 'mappings.json', []) as $key => $value) {

            if (stripos($value, '@') !== false) {
                // we have an @ - callable
                $callable = explode('@', $value);
                if (is_callable($callable)) {
                    //dd(call_user_func($callable, $arguments[0]));
                    $json = str_ireplace('"{' . $key . '}"', (call_user_func($callable, $arguments[0])), $json);
                }
            } elseif ($this->checkBool(array_get($arguments[0], $value))) {
                // Check boolean
                $json = str_ireplace('"{' . $key . '}"', (array_get($arguments[0], $value) ? array_get($arguments[0], $value) : "false"), $json);
            } else {
                $json = str_ireplace('{' . $key . '}', array_get($arguments[0], $value, 'UNKNOWN'), $json);
            }
        }

        return json_decode($json, true);
    }

    private function checkBool($string)
    {
        return in_array($string, ["true", "false", "1", "0", "yes", "no", true, false], true);
    }
}