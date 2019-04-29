<?php

namespace Lde\ApiHelper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Lde\ApiHelper\ApiResponse;
use Lde\ApiHelper\Events\ApiCallCompleted;
use Lde\ApiHelper\Events\ApiCallStarting;
use Lde\ApiHelper\Helpers\HelperException;
use Lde\ApiHelper\Helpers\ObfuscationHelper;
use Spatie\ArrayToXml\ArrayToXml;

class ApiBuilder
{
    public $type;

    public $baseUrl;

    public $connection;

    public $requestOptions = [];

    public $name;

    public $sensitiveFields = [];

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

        // Set the request options if provided for this conenction. Else use default ones.
        if (array_get($conn, 'default_request_options')) {
            $additionalHeaders = $this->requestOptions['headers'];
            $default = array_get($conn, 'default_request_options.headers');
            $headers = array_merge($additionalHeaders, $default);
            $this->requestOptions = array_get($conn, 'default_request_options');
            $this->requestOptions['headers'] = $headers;
        }

        // Set the api type
        $this->type = config('api_helper.connections.' . $this->connection . '.type');

        // Set the base url
        $this->baseUrl = config('api_helper.connections.' . $this->connection . '.base_url');

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

        $api = array_get($config['routes'], $name);

        $this->name = $this->connection . "\\" . $name;
        $object = new ApiResponse();
        if ($api) {

            // Raise starting event
            ApiCallStarting::dispatch($this->name, $api);

            // Method
            $method = strtoupper(array_get($api, 'method', 'GET'));

            // Uri
            if (!$uri = $this->baseUrl . array_get($api, 'uri')) {
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
                            // JSON mappings
                            $json = $this->processJsonMappings($arguments, $api);

                            // Call the API
                            $object = $this->call($method, $uri, ['json' => $json]);

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
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function call($method, $uri, $params = [])
    {

        $config = config('api_helper.connections.' . $this->connection);

        $tries = 0;
        $success = false;

        // Check retries and fall back to global retries or fall back to 3
        $retries = array_get($config, 'number_of_retries', config('api_helper.retries', 3));
        $object = new ApiResponse();
        $xml_data = '';
        while ($success == false && $tries <= $retries) {
            $tries++;

            $client = new Client();
            try {

                // Merge params
                $params = array_merge($this->requestOptions, $params);
                if ($this->type == "xml") {

                    Log::debug('Headers data: ', [
                        'data' => $uri,
                    ]);

                    // Send request
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
                $statusesNotToRetry = [400, 401, 404, 406, 422];

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
        $json = json_encode(array_get($api, 'body', []));
        foreach (array_get($api, 'mappings.body', []) as $key => $value) {

            if (stripos($value, '@') !== false) {
                // we have an @ - callable
                $callable = explode('@', $value);
                if (is_callable($callable)) {
                    $json = str_ireplace('"{' . $key . '}"', (call_user_func($callable, $arguments[0])), $json);
                }
            } elseif ($this->checkBool(array_get($arguments[0], $value))) {
                // Check boolean
                $json = str_ireplace('"{' . $key . '}"', array_get($arguments[0], $value, 'UNKNOWN'), $json);
            } else {
                $json = str_ireplace('{' . $key . '}', array_get($arguments[0], $value, 'UNKNOWN'), $json);
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
        $attributes = array_get($api, 'xml_config.attributes');
        $useUnderScores = array_get($api, 'xml_config.use_underscores', true);
        $encoding = array_get($api, 'xml_config.encoding', true);

        $xml = ArrayToXml::convert(array_get($api, 'body', []), [
            'rootElementName' => $rootElementName,
            '_attributes' => $attributes,
        ], $useUnderScores, $encoding);

        foreach (array_get($api, 'mappings.body', []) as $key => $value) {
            // TODO: we can add more support like validator
            if (stripos($value, 'nullable|') !== false) {
                $values = explode('|', $value);
                if (array_get($arguments[0], $values[1]) === null || array_get($arguments[0], $values[1]) === '') {
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
            } elseif ($this->checkBool(array_get($arguments[0], $value))) {
                // Check boolean
                $xml = str_ireplace('"{' . $key . '}"', array_get($arguments[0], $value, 'UNKNOWN'), $xml);
            } else {
                $xml = str_ireplace('{' . $key . '}', $this->escapeSpecialCharacters(array_get($arguments[0], $value, 'UNKNOWN')), $xml);
            }
        }
        // XML API don't allow & in value
        $xml = str_ireplace('&', ' &amp; ', $xml);
        return $xml;
    }

    /**
     * @param  String $string
     *
     * @return String $string
     * Remove special characters fomr xml string before request to the api
     */

    private function escapeSpecialCharacters(String $string): String
    {

        if (!empty($string)) {
            $string = preg_replace('/&(\w+);/i', '', $string);
        }
        return $string;

    }

    private function checkBool($string)
    {
        if (!empty($string)) {
            $string = strtolower($string);
            $string = (in_array($string, ["true", "false", "1", "0", "yes", "no"], true));
        }
        return $string;

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
        $dot = new \Adbar\Dot($data);

        foreach ($paths as $field) {

            $string = array_get($data, $field);

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
