<?php

namespace Lde\ApiHelper;

use Adbar\Dot;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Lde\ApiHelper\ApiResponse;
use Spatie\ArrayToXml\ArrayToXml;
use Illuminate\Support\Facades\Log;
use Lde\ApiHelper\Helpers\StatsHelper;
use Lde\ApiHelper\Events\ApiCallStarting;
use GuzzleHttp\Exception\RequestException;
use Lde\ApiHelper\Events\ApiCallCompleted;
use Lde\ApiHelper\Helpers\HelperException;
use Lde\ApiHelper\Helpers\ObfuscationHelper;

class ApiBuilder
{
    public $type;

    public $baseUrl;

    public $connection;

    public $requestOptions = [];

    public $name;

    public $sensitiveFields = [];

    public $client = null;

    /**
     * Sets API connection
     *
     * @param  mixed $connection
     *
     * @return ApiBuilder
     */
    public function api($connection)
    {
        // Set the sensitive field array
        $this->sensitiveFields = config('api_helper.sensitive_fields', []);

        // Setting up connection
        $this->connection = (!empty($connection)) ? $connection : config('api_helper.default');

        // Set the default request options
        $this->requestOptions = config('api_helper.default_request_options', []);

        // Check if connection provide in configuration file
        $conn = config('api_helper.connections.' . $connection);
        if (!$conn || !is_array($conn)) {
            throw new HelperException("Connection '$connection' not found!");
        }

        // Set the request options if provided for this connection. Else use default ones.
        if (Arr::get($conn, 'default_request_options')) {
            $additionalHeaders = $this->requestOptions['headers'];
            $default = Arr::get($conn, 'default_request_options.headers');
            $headers = array_merge($additionalHeaders, $default);
            $this->requestOptions = Arr::get($conn, 'default_request_options');
            $this->requestOptions['headers'] = $headers;
        }

        // Set the api type
        $this->type = config('api_helper.connections.' . $this->connection . '.type');

        // Set the base url
        $this->baseUrl = config('api_helper.connections.' . $this->connection . '.base_url');

        $this->client = new Client();

        return $this;
    }

    /**
     * Add header to request options
     *
     * @param array  $headers
     *
     * @return ApiBuilder
     */
    public function addHeaders(array $headers): ApiBuilder
    {
        foreach ($headers as $key => $value) {
            // Add header to requestOptions
            $this->requestOptions['headers'][$key] = $value;
        }

        return $this;
    }

    /**
     * Magic method to call api
     *
     * Call API using name provided in settings, eg $api->get_users($data)
     *
     * @param $name
     * @param $arguments
     *
     * @return array
     * @throws \App\Exceptions\HelperException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __call($name, $arguments)
    {
        // Start the timer
        $startTime = microtime(true);

        $config = config('api_helper.connections.' . $this->connection);

        $api = Arr::get($config['routes'], $name);

        $this->name = $this->connection . "\\" . $name;
        $object = new ApiResponse();
        if ($api) {

            // Raise starting event
            ApiCallStarting::dispatch($this->name, $api);

            // Method
            $method = strtoupper(Arr::get($api, 'method', 'GET'));
            $requestType = Arr::get($api, 'request_type');

            // Uri
            if (!$uri = $this->baseUrl . Arr::get($api, 'uri')) {
                throw new HelperException("Uri is not configured for {$name} API!");
            }

            // Path mappings
            $uri = $this->processPathMappings($arguments, $api, $uri);

            // Query mappings
            $uri = $this->processQueryMappings($arguments, $api, $uri);

            // type
            switch ($this->type) {
                case 'json':

                    switch ($method) {
                        // only post and put have a body
                        case 'PATCH':
                        case 'POST':
                        case 'PUT':
                            // JSON or Form_params mappings

                            if ($requestType == 'form_data') {

                                $json = $this->processFormParamsMappings($arguments, $api);

                                $object = $this->call($method, $uri, ['form_params' => $json]);
                            } else {

                                $json = $this->processJsonMappings($arguments, $api);
                                // Call the API
                                $object = $this->call($method, $uri, ['json' => $json]);
                            }

                            break;
                        default:
                            $json = [];

                            // Call the API
                            $object = $this->call($method, $uri);
                    }

                    // check for success
                    if ($object->success == true) {
                        // Decode JSON body
                        //$object->data = json_decode($response->data, true);

                        Log::info('ApiBuilder->' . $name . '() - Call succeeded', [
                            'api_name' => $this->name,
                            'method' => $method,
                            'uri' => $uri,
                            'params' => $json,
                            'response' => $object,
                        ]);

                    } else {

                        Log::info('ApiBuilder->' . $name . '() - Call failed', [
                            'api_name' => $this->name,
                            'method' => $method,
                            'uri' => $uri,
                            'params' => $json,
                            'response' => $object,
                        ]);

                    }

                    break;
                case 'xml':
                    
                    switch ($method) {
                        // only post and put have a body
                        case 'PATCH':
                        case 'POST':
                        case 'PUT':
                            // JSON mappings
                            $xml = $this->processXmlMappings($arguments, $api);

                            // Set XML headers
                            $this->addHeaders([
                                'Accept' => 'application/xml',
                                'Content-Type' => 'application/xml',
                            ]);

                            // Call the API
                            $object = $this->call($method, $uri, ['body' => $xml]);

                            break;
                        default:
                            $xml = '';

                            // Call the API
                            $object = $this->call($method, $uri);
                    }

                    // check for success
                    if ($object->success == true) {
                        // Decode XML Body
                        //$object->data = json_decode(json_encode(simplexml_load_string($response->data)), true);

                        Log::info('ApiBuilder->' . $name . '() - Call succeeded', [
                            'api_name' => $this->name,
                            'method' => $method,
                            'uri' => $uri,
                            'params' => $xml,
                            'response' => $object,
                        ]);
                    } else {
                        Log::info('ApiBuilder->' . $name . '() - Call failed', [
                            'api_name' => $this->name,
                            'method' => $method,
                            'uri' => $uri,
                            'params' => $xml,
                            'response' => $object,
                        ]);
                    }

                    break;
                default:
                    throw new HelperException('API type ' . $this->type . ' is not defined!');
            }

            // check for success
            if ($object->success == true) {
                // Raise completed event
                ApiCallCompleted::dispatch($this->name, $object, $api, microtime(true) - $startTime);
            } else {
                // Raise failed event
                ApiCallCompleted::dispatch($this->name, $object, $api, microtime(true) - $startTime, $object->error);
            }

            // Log to prom if it is enabled
            if (config('api_helper.log_stats')) {
                $bucketName = config('api_helper.prometheus.histogram_bucket_name');
                if (empty($bucketName)) {
                    $bucketName = 'external_apis_response_time_seconds';
                }
                StatsHelper::incHistogram($bucketName, (float) (microtime(true) - $startTime), [$this->connection, $name, $object->meta->status_code], "Response time for external API calls.", ['destination', 'endpoint', 'status']);
            }
            
            return $object;

        } else {
            throw new HelperException("Api {$this->name} is not configured!");
        }
    }

    /**
     * @param $method
     * @param $uri
     * @param $params
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function call($method, $uri, $params = [])
    {

        $config = config('api_helper.connections.' . $this->connection);

        $tries = 0;
        $success = false;

        // Check retries and fall back to global retries or fall back to 3
        $retries = Arr::get($config, 'number_of_retries', config('api_helper.retries', 3));
        $object = new ApiResponse();
        $xml_data = '';
        while ($success == false && $tries <= $retries) {
            $tries++;

            if (is_null($this->client)) {
                $this->client = new Client();
            }

            $client = $this->client;
            try {

                // Merge params
                $params = array_merge($this->requestOptions, $params);
                if ($this->type == "xml") {

                    Log::debug('Headers data: ', [
                        'data' => $uri,
                    ]);

                    $response = $client->request($method, $uri, $params);                    
                    // If we got this far, we have a response.

                    // convert xml string into an object
                    $data = (array) simplexml_load_string($response->getBody()->getContents());

                } else {

                    // Send request
                    $response = $client->request($method, $uri, $params);

                    // If we got this far, we have a response.
                    $data = json_decode((string) $response->getBody(), true);
                }

                Log::debug('ApiBuilder->call() - Call succeeded', [
                    'api_name' => $this->name,
                    'method' => $method,
                    'uri' => $uri,
                    'params' => $this->maskFieldValues($params, ['auth.0', 'auth.1', 'headers.apikey']),
                    'response' => $data,
                    'tries' => $tries,
                ]);

                $object->success = true;
                $object->body = $data;
                $object->meta = new \stdClass();
                $object->meta->method = $method;
                $object->meta->uri = $uri;
                $object->meta->params = $this->maskFieldValues($params, ['auth.0', 'auth.1', 'headers.apikey']);
                $object->meta->status_code = $response->getStatusCode();
                $object = $object->withStatus($response->getStatusCode());
                $object->meta->response = $response;
                $object->meta->tries = $tries;
                return $object;
            } catch (RequestException $ex) {

                $httpStatusCode = $ex->hasResponse() && $ex->getResponse() ? $ex->getResponse()->getStatusCode() : 500;
                $httpStatus = $ex->hasResponse() && $ex->getResponse() ? $ex->getResponse()->getReasonPhrase() : '';
                $httpBody = $ex->hasResponse() && $ex->getResponse() ? $ex->getResponse()->getBody()->getContents() : '';

                Log::info("ApiBuilder threw a RequestException", [
                    'api_name' => $this->name,
                    'method' => $method,
                    'uri' => $uri,
                    'params' => $this->maskFieldValues($params, ['auth.0', 'auth.1', 'headers.apikey']),
                    'error' => $ex->getMessage(),
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'http_status_code' => $httpStatusCode,
                    'http_status' => $httpStatus,
                    'tries' => $tries,
                ]);

                // unset $client
                unset($client);

                $object->success = false;
                $object->error = $ex->getMessage();
                $object->body = $httpBody;
                $object->meta = new \stdClass();
                $object->meta->method = $method;
                $object->meta->uri = $uri;
                $object->meta->params = $this->maskFieldValues($params, ['auth.0', 'auth.1', 'headers.apikey']);
                $object->meta->status_code = $httpStatusCode;
                $object = $object->withStatus($httpStatusCode);
                $object->meta->tries = $tries;

                // Check if we should retry
                $configStatus = Arr::get($config, 'status_not_to_retry', []);
                $defaultStatus = [400, 401, 404, 406, 422];
                $statusesNotToRetry = array_merge($configStatus, $defaultStatus);

                if (in_array($httpStatusCode, $statusesNotToRetry)) {
                    Log::debug('ApiBuilder->call() - Call failed but status is in blacklist. Not retrying.', [
                        'api_name' => $this->name,
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

                Log::info("ApiBuilder threw an Exception", HelperException::toArray($ex, [
                    'api_name' => $this->name,
                    'method' => $method,
                    'uri' => $uri,
                    'params' => $this->maskFieldValues($params, ['auth.0', 'auth.1', 'headers.apikey']),
                    'tries' => $tries,
                ]));

                // unset $client
                unset($client);

                $object->success = false;
                $object->error = $ex->getMessage();
                $object->body = null;
                $object->meta = new \stdClass();
                $object->meta->method = $method;
                $object->meta->uri = $uri;
                $object->meta->params = $this->maskFieldValues($params, ['auth.0', 'auth.1', 'headers.apikey']);
                $object->meta->status_code = $httpStatusCode;
                $object = $object->withStatus($httpStatusCode);
                $object->meta->tries = $tries;
                return $object;
            }
        }

        // We got here, this means we ran out of retries
        Log::info("ApiBuilder '{$method}' had a fatal failure. No more retries. Giving up.", [
            'api_name' => $this->name,
            'method' => $method,
            'uri' => $uri,
            'params' => $this->maskFieldValues($params, ['auth.0', 'auth.1', 'headers.apikey']),
            'error' => $object->error,
            'tries' => $tries,
        ]);

        return $object;
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
        foreach (Arr::get($api, 'mappings.path', []) as $key => $value) {
            $uri = str_ireplace('{' . $key . '}', Arr::get($arguments[0], $value, null), $uri);
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
        foreach (Arr::get($api, 'mappings.query', []) as $key => $value) {
            if (Arr::get($arguments[0], $value) !== null) {
                $query[$key] = Arr::get($arguments[0], $value, '');
            }
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
        $json = json_encode(Arr::get($api, 'body', []));
        if ($json === '[]') {
            return [];
        }
        foreach (Arr::get($api, 'mappings.body', []) as $key => $value) {

            //Remove array key which is nullable, Need to specify "nullable" in api_helper.php config file.
            if (stripos($value, 'nullable|') !== false) {
                $values = explode('|', $value);
                if (Arr::get($arguments[0], $values[1]) === null || Arr::get($arguments[0], $values[1]) === '') {
                    $stringToArray = json_decode($json, true);
                    unset($stringToArray[$key]);
                    $json = json_encode($stringToArray);
                    continue;
                } else {
                    $value = $values[1];
                }
            }

            if (stripos($value, '@') !== false) {
                // we have an @ - callable
                $callable = explode('@', $value);
                if (is_callable($callable)) {
                    $json = str_ireplace('"{' . $key . '}"', (call_user_func($callable, $arguments[0])), $json);
                }
            } elseif ($this->checkBool(Arr::get($arguments[0], $value))) {
                // Check boolean
                $json = str_ireplace('"{' . $key . '}"', Arr::get($arguments[0], $value, null), $json);
            } else {
                $json = str_ireplace('{' . $key . '}', Arr::get($arguments[0], $value, null), $json);
            }
        }
        $mapping = json_decode($json, true);
        if ($mapping == null) {
            Log::error("ApiBuilder->processJsonMappings() - Error while decoding string", [
                'json' => $json,
                'arguments' => $arguments,
                'api' => $api,
            ]);
            return [];
        }
        return $mapping;
    }

    /**
     * @param $arguments
     * @param $api
     *
     * @return array
     */
    protected function processFormParamsMappings($arguments, $api): array
    {
        $json = json_encode(Arr::get($api, 'form_params', []));

        foreach (Arr::get($api, 'mappings.form_params', []) as $key => $value) {
            //Remove array key which is nullable, Need to specify "nullable" in api_helper.php config file.
            if (stripos($value, 'nullable|') !== false) {
                $values = explode('|', $value);
                if (Arr::get($arguments[0], $values[1]) === null || Arr::get($arguments[0], $values[1]) === '') {
                    $stringToArray = json_decode($json, true);
                    unset($stringToArray[$key]);
                    $json = json_encode($stringToArray);
                    continue;
                } else {
                    $value = $values[1];
                }
            }
            if (stripos($value, '@') !== false) {
                // we have an @ - callable
                $callable = explode('@', $value);
                if (is_callable($callable)) {
                    $json = str_ireplace('"{' . $key . '}"', (call_user_func($callable, $arguments[0])), $json);
                }
            } elseif ($this->checkBool(Arr::get($arguments[0], $value))) {
                // Check boolean
                $json = str_ireplace('"{' . $key . '}"', Arr::get($arguments[0], $value, null), $json);
            } else {
                $json = str_ireplace('{' . $key . '}', Arr::get($arguments[0], $value, null), $json);
            }
        }
        return json_decode($json, true);
    }

    /**
     * @param $arguments
     * @param $api
     *
     * @return string
     */
    protected function processXmlMappings($arguments, $api): string
    {
        // get xml config
        $rootElementName = (!empty($api['xml_config']['root_element_name'])) ? $api['xml_config']['root_element_name'] : ((!empty(config('api_helper.connections.' . $this->connection . '.root'))) ? config('api_helper.connections.' . $this->connection . 'root') : 'request');
        $attributes = Arr::get($api, 'xml_config.attributes');
        $useUnderScores = Arr::get($api, 'xml_config.use_underscores', true);
        $encoding = Arr::get($api, 'xml_config.encoding', true);

        $xml = ArrayToXml::convert(Arr::get($api, 'body', []), [
            'rootElementName' => $rootElementName,
            '_attributes' => $attributes,
        ], $useUnderScores, $encoding);

        foreach (Arr::get($api, 'mappings.body', []) as $key => $value) {
            // TODO: we can add more support like validator
            if (stripos($value, 'nullable|') !== false) {
                $values = explode('|', $value);
                if (Arr::get($arguments[0], $values[1]) === null || Arr::get($arguments[0], $values[1]) === '') {
                    $xml = str_ireplace('<' . $key . '>{' . $key . '}</' . $key . '>', '', $xml);
                    continue;
                } else {
                    $value = $values[1];
                }
            }
            if (stripos($value, '@') !== false) {
                // we have an @ - callable
                $callable = explode('@', $value);
                if (is_callable($callable)) {
                    $xml = str_ireplace('{' . $key . '}', $this->escapeSpecialCharacters((call_user_func($callable, $arguments[0]))), $xml);
                }
            } elseif ($this->checkBool(Arr::get($arguments[0], $value))) {
                // Check boolean
                if (!empty(Arr::get($arguments[0], $value))) {
                    $xml = str_ireplace('{' . $key . '}', Arr::get($arguments[0], $value), $xml);
                } else {
                    $xml = str_ireplace('<' . $key . '>{' . $key . '}</' . $key . '>', '', $xml);
                }
            } else {
                if (!empty(Arr::get($arguments[0], $value))) {
                    $xml = str_ireplace('{' . $key . '}', $this->escapeSpecialCharacters(Arr::get($arguments[0], $value)), $xml);
                } else {
                    $xml = str_ireplace('<' . $key . '>{' . $key . '}</' . $key . '>', '', $xml);
                }
            }
        }
        // XML API don't allow & in value
        $xml = str_ireplace('&', '&amp;', $xml);
        return $xml;
    }

    /**
     * @param  String $string
     *
     * @return String $string
     * Remove special characters form xml string before request to the api
     */

    private function escapeSpecialCharacters(String $string): String
    {
        if (!empty($string)) {
            $custom_escape_method = config('api_helper.connections.' . $this->connection . '.character_escape_method');
            if (!empty($custom_escape_method)) {
                if (stripos($custom_escape_method, '@') !== false) {
                    $callable = explode('@', $custom_escape_method);
                    if (is_callable($callable) === true) {
                        $string = call_user_func($callable, $string);
                    }
                }
            } else {
                $string = preg_replace('/&(\w+);/i', '', $string);
            }
        }
        return $string;
    }

    private function checkBool($string)
    {
        if (!empty($string) && gettype($string) === 'string') {
            $string = strtolower($string);
            $string = (in_array($string, ["true", "false", "1", "0", "yes", "no"], true));
            return $string;
        }

    }

    /**
     * Mask sensitive fields so they are not logged
     *
     * @param  mixed $paths
     *
     * @return void
     */
    protected function maskFieldValues(array &$data, array $paths)
    {
        $dot = new Dot($data);

        foreach ($paths as $field) {

            $string = Arr::get($data, $field);

            if (stripos($string, '@') !== false) {
                $obfuscatedString = ObfuscationHelper::obfuscate($string, 4);
            } else {
                $obfuscatedString = ObfuscationHelper::obfuscate($string, 4);
            }

            // Set the masked values
            $dot->set($field, $obfuscatedString);

            // Alternative to obfuscate
            // array_forget($data, $field);
        }

        return $dot->all();
    }
}
