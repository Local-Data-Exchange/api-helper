<?php
namespace Lde\ApiHelper\Tests;

use Illuminate\Support\Facades\Facade;

class ApiHelperFacadeTest extends Facade
{
    protected static function getFacadeAccessor() { 
        return 'apibuildertest';
    }
}