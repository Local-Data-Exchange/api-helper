



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
