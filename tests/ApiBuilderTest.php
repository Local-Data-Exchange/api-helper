<?php

namespace Lde\ApiHelper\Tests;

use Lde\ApiHelper\ApiBuilder;

class ApiBuilderTest extends TestCase
{

    public function testCanSetConnection()
    {
        $apibuilder = new ApiBuilder();
        $api = $apibuilder->api('httpbin');
        self::assertEquals($api->connection, 'httpbin');
    }

    public function testCanAddHeaders()
    {
        $apibuilder = new ApiBuilder();
        $api = $apibuilder->api('httpbin')->addHeaders(['test' => 'unit', 'foo' => 'bar']);
        self::assertEquals($api->requestOptions['headers']['test'], 'unit');
        self::assertEquals($api->requestOptions['headers']['foo'], 'bar');
    }

    public function testHttpBinGet()
    {
        $apibuilder = new ApiBuilder();
        $api = $apibuilder->api('httpbin');
        $response = $api->get(['person' => ['name' => 'John', 'surname' => 'Doe'], 'foo' => 'Foobar']);
        self::assertTrue($response->success);
        self::assertEquals('John', $response->body['args']['name']);
        self::assertEquals('Doe', $response->body['args']['surname']);
        self::assertEquals('Foobar', $response->body['args']['foo']);
    }

    public function testHttpBinPost()
    {
        $apibuilder = new ApiBuilder();
        $api = $apibuilder->api('httpbin');
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
        $apibuilder = new ApiBuilder();
        $api = $apibuilder->api('httpbin')->addHeaders($header);
        $response = $api->post(['person' => ['name' => 'John', 'surname' => 'Doe'], 'foo' => 'This is Foobar!']);
        self::assertTrue($response->success);
        self::assertEquals('123456', $response->body['headers']['X-Org-Api-Key']);
    }

    public function testMagicDelete()
    {
        $apibuilder = new ApiBuilder();
        $api = $apibuilder->api('httpbin');
        $response = $api->delete(['person' => ['id' => 'xyz']]);
        self::assertTrue($response->success);
        self::assertEquals('xyz', $response->body['args']['id']);
        self::assertEquals('https://httpbin.org/delete?id=xyz', $response->body['url']);
    }

    public function testHttpBinPostXml()
    {
        $apibuilder = new ApiBuilder();
        $api = $apibuilder->api('mockbin');
        $response = $api->echo(['request' => ['name' => 'John', 'class' => 'Barbarian', 'weapon' => 'Dagger']]);
        $val = $response->body['request'];
        self::assertTrue($response->success);
        self::assertEquals('John', $val->name);
        self::assertEquals('Dagger', $val->weapon);
        self::assertEquals('Barbarian', $val->attributes->class[0]);
        self::assertEquals('http://mockbin.org/echo', $response->meta->uri);
    }
    public function testHttpBinPostFormParams()
    {
        $apibuilder = new ApiBuilder();
        $api = $apibuilder->api('httpbin');
        $api = $api->addHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
        $response = $api->formParams(['param' => ['revision' => 'one']]);
        self::assertTrue($response->success);
        self::assertArrayHasKey('form',$response->body);
        self::assertEquals('https://httpbin.org/post', $response->meta->uri);
        self::assertEquals($api->requestOptions['headers']['Content-Type'],'application/x-www-form-urlencoded');
        self::assertEquals('one',$response->meta->params['form_params']['parameters']['revision']);
    }
}
