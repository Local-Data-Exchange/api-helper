<?php

return [

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

    'number_of_retries' => 3,

    /*
    |--------------------------------------------------------------------------
    | Default Guzzle Request Options
    |--------------------------------------------------------------------------
    |
    | Sets the default Guzzle request options.
    |
    | http://docs.guzzlephp.org/en/stable/request-options.html
    |
    | 0 = Off
    |
     */

    'default_request_options' => [
        'http_errors' => true,
        'connect_timeout' => 10,
        'timeout' => 30,
        'headers' => [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ],
    ],

    // If no connection provided, use default
    'default' => 'httpbin',

    // Sensitive fields will be masked on the return value and logs
    'sensitive_fields' => [
        'auth.0',
        'auth.1',
        'headers.apikey',
    ],

    // Define all the APIs here
    'connections' => [

        // HTTPBin
        'httpbin' => [
            'root' => '',
            // API type: json or xml or view
            'type' => 'json',

            // API base URL
            'base_url' => 'https://httpbin.org',

            // Default Request options. these are included with all calls unless overwritten
            'default_request_options' => [
                'http_errors' => true,
                'connect_timeout' => 10,
                'timeout' => 30,
                'headers' => [
                    "Accept" => "application/json",
                    "Content-Type" => "application/json",
                ],
            ],

            // number of retries to override global settings.
            'number_of_retries' => 0, // 0 = off

            // List of API routes we are integrating with
            'routes' => [

                // Sample API to test GET
                'get' => [
                    'method' => 'GET',
                    'uri' => '/get',
                    'mappings' => [
                        'path' => [],
                        'query' => [
                            'name' => 'person.name',
                            'surname' => 'person.surname',
                            'foo' => 'foo',
                        ],
                        'body' => [],
                    ],
                ],

                // Sample API to test POST
                'post' => [
                    'name' => 'httpbin',
                    'method' => 'POST',
                    'uri' => '/post',
                    'body' => [
                        'first_name' => '{name}',
                        'last_name' => '{surname}',
                        'nested' => [
                            'foo' => '{foo}',
                        ],
                    ],
                    'mappings' => [
                        'query' => [
                            'test' => 'person.name',
                        ],
                        'body' => [
                            'name' => 'person.name',
                            'surname' => 'person.surname',
                            'foo' => 'foo',
                        ],
                    ],
                ],

                'delete' => [
                    'name' => 'httpbin',
                    'method' => 'DELETE',
                    'uri' => '/delete',
                    'mappings' => [
                        'query' => [
                            'id' => 'person.id',
                        ],
                    ],
                ],

            ],
        ],

        'mockbin' => [
            'root' => 'request',
            // API type: json or xml or view
            'type' => 'xml',

            // API base URL
            'base_url' => 'http://mockbin.org',

            // Default Request options. these are included with all calls unless overwritten
            'default_request_options' => [
                'http_errors' => true,
                'connect_timeout' => 10,
                'timeout' => 30,
                'headers' => [
                    "Accept" => "application/xml",
                    "Content-Type" => "application/xml",
                ],
            ],

            // number of retries to override global settings.
            'number_of_retries' => 0, // 0 = off

            'routes' => [
                // Sample API to test POST using xml as the body
                'echo' => [
                    'method' => 'POST',
                    'uri' => '/echo',
                    'request_options' => [ // we can override request_options per API
                        'http_errors' => true,
                        'connect_timeout' => 10,
                        'timeout' => 30,
                        'headers' => [ // these headers will be set automatically for XML apis. No need to specify them
                            "Accept" => "application/xml",
                            "Content-Type" => "application/xml",
                        ],
                    ],
                    'xml_config' => [
                        'root_element_name' => 'request', // defaults to root if left out
                        'attributes' => [
                            'xmlns' => 'https://github.com/spatie/array-to-xml',
                        ],
                        'use_underscores' => true,
                        'encoding' => 'UTF8',
                    ],
                    'body' => [
                        'request' => [
                            'attributes' => ['class' => '{class}'],
                            'name' => '{name}',
                            'weapon' => '{weapon}',
                        ],
                    ],
                    'mappings' => [
                        'body' => [
                            'class' => 'person.class',
                            'name' => 'person.name',
                            'weapon' => 'person.weapon',
                        ],
                    ],
                ],
            ],
        ],
    ],
];