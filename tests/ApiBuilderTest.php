<?php

namespace Tests;

use ApiHelper;

class ApiBuilderTest extends TestCase
{

    public function testCanSetConnection()
    {
        //$api = ApiHelper::api('httpbin');
        $api = ApiHelper::api('httpbin');
        self::assertEquals($api->connection, 'httpbin');
    }

    public function testCanAddHeaders()
    {
        $api = ApiHelper::api('httpbin')->addHeaders(['test' => 'unit', 'foo' => 'bar']);
        self::assertEquals($api->requestOptions['headers']['test'], 'unit');
        self::assertEquals($api->requestOptions['headers']['foo'], 'bar');
    }

    public function testHttpBinGet()
    {
        $api = ApiHelper::api('httpbin');
        $response = $api->get(['person' => ['name' => 'John', 'surname' => 'Doe'], 'foo' => 'Foobar']); 
        self::assertTrue($response->success);
        self::assertEquals('John', $response->body['args']['name']);
        self::assertEquals('Doe', $response->body['args']['surname']);
        self::assertEquals('Foobar', $response->body['args']['foo']);
    }

    public function testHttpBinPost()
    {
        $api = ApiHelper::api('httpbin');
        $response = $api->post(['person' => ['name' => 'John', 'surname' => 'Doe'], 'foo' => 'This is Foobar!']);
        self::assertTrue($response->success);
        self::assertEquals('{"first_name":"John","last_name":"Doe","nested":{"foo":"This is Foobar!"}}', $response->body['data']);
        self::assertEquals([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nested' => [
                'foo' => 'This is Foobar!',
            ],
        ], $response->body['json']);
        self::assertEquals('https://httpbin.org/post?test=John', $response->body['url']);
    }

    public function testMagicPostWithAuthHeader()
    {
        $header['x-org-api-key'] = '123456';
        $api = ApiHelper::api('httpbin')->addHeaders($header);
        $response = $api->post(['person' => ['name' => 'John', 'surname' => 'Doe'], 'foo' => 'This is Foobar!']);
        self::assertTrue($response->success);
        self::assertEquals('123456', $response->body['headers']['X-Org-Api-Key']);
    }

    public function testMagicDelete()
    {
        $api = ApiHelper::api('httpbin');
        $response = $api->delete(['person' => ['id' => 'xyz']]);
        self::assertTrue($response->success);
        self::assertEquals('xyz', $response->body['args']['id']);
        self::assertEquals('https://httpbin.org/delete?id=xyz', $response->body['url']);
    }

    public function testHttpBinPostXml()
    {
        $api = ApiHelper::api('mockbin');
        $response = $api->echo(['request' => ['name' => 'John', 'class' => 'Barbarian', 'weapon' => 'Dagger']]);
        self::assertTrue($response->success);        
        self::assertEquals('John', $response->body->request->name);
        self::assertEquals('Dagger', $response->body->request->weapon);
        self::assertEquals('Barbarian', $response->body->request['class'][0]);
        self::assertEquals('http://mockbin.org/echo', $response->meta->uri);
    }

}
