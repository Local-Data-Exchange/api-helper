<?php
namespace Lde\ApiHelper;

use Illuminate\Support\Facades\Facade;

class ApiHelperFacadeTest extends Facade
{
    protected static function getFacadeAccessor() { 
        return 'apibuilder';
    }
}