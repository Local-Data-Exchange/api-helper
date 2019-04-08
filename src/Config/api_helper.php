<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default name
    |--------------------------------------------------------------------------
    |
    | Sets the name of the API in the logs for easy debugging
    |
     */

    'default_name'            => 'default',

    /*
    |--------------------------------------------------------------------------
    | Billing Logger
    |--------------------------------------------------------------------------
    |
    | Enables the use of BillingLogger for logging the billing event.
    |
     */

    'use_billing_logger'      => true,

    /*
    |--------------------------------------------------------------------------
    | Stats Logger
    |--------------------------------------------------------------------------
    |
    | Enables the use of StatsLogger for logging statistics to Prometheus
    |
     */

    'use_stats_logger'        => true,

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Sets the number of retries to attempt before giving up.
    |
    | 0 = Off
    |
     */

    'number_of_retries'       => 3,

    /*
    |--------------------------------------------------------------------------
    | Default Guzzle Request Options
    |--------------------------------------------------------------------------
    |
    | Sets the default Guzzle request options.
    |
    | http://docs.guzzlephp.org/en/stable/request-options.html
    |
     */

    'default_request_options' => [
        'http_errors'     => true,
        'connect_timeout' => 10,
        'timeout'         => 30,
        'headers'         => [
            "Accept"       => "application/json",
            "Content-Type" => "application/json",
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | APIs
    |--------------------------------------------------------------------------
    |
    | Configure all the APIs that will be used in the app.
    |
     */

    'apis'                    => [

        /*
        |--------------------------------------------------------------------------
        | Sample API configuration
        |--------------------------------------------------------------------------
        |
        | Configure all the APIs that will be used in the app.
        |
        | Name: Optional. Defaults to the default_name
        | Method: API method. One of GET, POST, DELETE, PUT
        | URI: API Uri
        | JSON: JSON payload of the API represented as an array. Keys, eg {key} will be
        |       replaced using mapping in the mappings section
        | Mappings: Defines the mappings to add into the PATH, QUERYSTRING and JSON
        |
        |
         */

        'sample'         => [
            'name'     => 'test', // optional - defaults to default_name
            'method'   => 'GET', // Api method. one of GET, POST, DELETE, PUT
            'uri'      => '', // API Uri
            'json'     => [], // Json that is being sent to API as array
            'mappings' => [
                'path'  => [], // Values to be replaced in the Uri path
                'query' => [], // Values to be appended to the Uri
                'json'  => [], // Values to be replaced in the Json
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | HTTPBin API
        |--------------------------------------------------------------------------
        |
        | API used for testing
        |
        | https://httpbin.org
        |
         */

        'httpbin_get'    => [
            'name'     => 'httpbin',
            'method'   => 'GET',
            'uri'      => 'https://httpbin.org/get',
            'mappings' => [
                'path'  => [],
                'query' => [
                    'name'    => 'person.name',
                    'surname' => 'person.surname',
                    'foo'     => 'foo',
                ],
                'json'  => [],
            ],
        ],

        'httpbin_post'   => [
            'name'     => 'httpbin',
            'method'   => 'POST',
            'uri'      => 'https://httpbin.org/post',
            'json'     => [
                'first_name' => '{name}',
                'last_name'  => '{surname}',
                'nested'     => [
                    'foo' => '{foo}',
                ],
            ],
            'mappings' => [
                'query' => [
                    'test' => 'person.name',
                ],
                'json'  => [
                    'name'    => 'person.name',
                    'surname' => 'person.surname',
                    'foo'     => 'foo',
                ],
            ],
        ],

        'httpbin_delete' => [
            'name'     => 'httpbin',
            'method'   => 'DELETE',
            'uri'      => 'https://httpbin.org/delete',
            'mappings' => [
                'query' => [
                    'id' => 'person.id',
                ],
            ],
        ],
    ],
];
