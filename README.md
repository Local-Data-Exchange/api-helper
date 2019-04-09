# LDE api-helper    

This package is useful to consume API's, here is the instruction for installation and usage.


# Installation

### Method - 1   
- To install this package using [Packagist](https://packagist.org/packages/lde/api-helper)  

- On the root of your project run following command   

		composer require lde/api-helper

- This command will install package with dependency

### Method - 2
- For this method you need to add following lines to your composer.json file which is placed on root of project

		"require": {
	        "lde/api-helper": "dev-master"
	    }
- After adding this line to your composer.json file run the following command

		composer update
- This will install package that is specified in the composer.json file with its dependency.
   
   
# Configuration

- After installing package some configuration need to be done to use package, Now go to your **Project->config->app.php** open this file.
- We need to register our provider and aliases to use it in project so add the following lines for **providers**

		Lde\ApiHelper\ApiHelperServiceProvider::class,
		
- Now we have to add aliases in same file add following line in aliases

		'ApiHelper' => Lde\ApiHelper\ApiHelperFacade::class,
- Next step is to publish config files to use with packages so, run the following command in your terminal to publish config file to config folder.

		php artisan vendor:publish
- After run this command it will ask you which provider do you want to publish in that select option that contain following option.

		Provider: Lde\ApiHelper\ApiHelperServiceProvider
- This will publish config file naming **api_helper.php** into config folder.

# Usage

- To use this package you need to add following namespace where you want to use this package.

		use ApiHelper;
		
- Now you can use functions of this packages. 

