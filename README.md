



<!-- PROJECT LOGO -->
<br />
<p align="center">
  <a href="https://www.localdataexchange.com">
    <img src="https://staging-ipromote.ldex.co/ctm/LDE_Logo-Black.png" alt="Logo" width="" height="80">
  </a>

  <h3 align="center">Api Helper Package</h3>

  <p align="center">
    A package to consume api smoothly
    <br />
    <a href="#table-of-contents"><strong>Explore the docs »</strong></a>
    <br />
    <br />
    <a href="https://packagist.org/packages/lde/api-helper">View Package</a>
    ·
    <a href="https://github.com/Local-Data-Exchange/api-helper/issues">Report Bug</a>
    ·
    <a href="https://github.com/Local-Data-Exchange/api-helper/issues">Request Feature</a>
  </p>
</p>



<!-- TABLE OF CONTENTS -->
## Table of Contents

* [Getting Started](#getting-started)
* [Installation](#installation)
* [Configuration](#configuration)
* [Usage](#usage)
	* [Methods](#methods)
* [Response](#response)
		



## Getting Started    

This package is useful to consume API's, here is the instruction for installation and usage.

## Installation
   
1. To install this package using [Packagist](https://packagist.org/packages/lde/api-helper)  

2. On the root of your project run following command   

		composer require lde/api-helper

3. This command will install package with dependency
  
## Configuration

- To use this apihelper need to export config file to do so run the following command in your terminal to publish config file to config folder.

	    php artisan vendor:publish  --provider="Lde\ApiHelper\ApiHelperServiceProvider"

- This will publish config file naming **api_helper.php** into config folder.

## Prometheus Configuration
- Prometheus is dependent on your app so you need to provide prometheus configuration and also use below packages on your app.

  - [jimdo/prometheus_client_php](https://github.com/Jimdo/prometheus_client_php)
  - [superbalist/laravel-prometheus-exporter](https://github.com/Superbalist/laravel-prometheus-exporter)
- If you want to use prometheus then you should turn it on from config [api_helper](src/Config/api_helper.php)
```php
'log_stats' => true, // If you want to use prometheus then set as true otherwise false

    'prometheus' => [
        'labels' => [           
            'client_id' => 10,
            'app' => 'api-helper',
            'source' => 'core',
        ],
        'histogram_bucket' => [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0, 7.5, 10.0],
    ],
```
- You can configure labels of prometheus inside `prometheus.labels` as per your need.
- `histogram_bucket` you can set inside prometheus config as array.

## Usage

- To use this package you need to add following class where you want to use this package.

		use Lde\ApiHelper\ApiBuilder;

		
### Methods

#### addHeaders($headers)

- This method is use to add headers.

- It accept name and value as parameter, Here you can set only one header at a time.


		$headers['Accept'] = "application/json"; 
		$headers['Content-Type'] = "application/json";  
		app(ApiBuilder::class)->addHeaders($headers);

- We will get response in form of object of ApiBuilder.


#### api($connection)

- This method is use to set api that we are going to use from *api_helper.php* , there is httpbin and mokbin is define so you have to pass the name that you want to use.

- You can also define your own api end point at *api_helper.php* in config file.
	
        app(ApiBuilder::class)->api('httpbin')->method_to_call();

- The snippet indicates how you can connect particular api and access their method.

- method_to_call() is the function that you have specified inside *api_helper* connection array.

- This will return object of ApiResponse.

### Response

- Here you will get object in response, In each response you will get success either true or false
- You will also get status code for more information about response please check below doc.
- http://docs.guzzlephp.org/en/latest/psr7.html#responses
