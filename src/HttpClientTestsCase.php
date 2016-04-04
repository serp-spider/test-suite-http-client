<?php
/**
 * @license see LICENSE
 */

namespace Serps\Test\HttpClient;

use Serps\Core\Cookie\ArrayCookieJar;
use Serps\Core\Cookie\Cookie;
use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\SearchEngineResponse;
use Zend\Diactoros\Request;

abstract class HttpClientTestsCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @return HttpClientInterface
     */
    abstract public function getHttpClient();


    public function testGetRequest()
    {
        $client = $this->getHttpClient();
        $request = new Request('http://httpbin.org/get', 'GET');
        $request = $request->withHeader('User-Agent', 'test-user-agent');
        $response = $client->sendRequest($request);
        $this->assertInstanceOf(SearchEngineResponse::class, $response);
        $responseData = json_decode($response->getPageContent(), true);
        $this->assertEquals(200, $response->getHttpResponseStatus());
        $this->assertEquals('test-user-agent', $responseData['headers']['User-Agent']);
        $this->assertEquals('http://httpbin.org/get', $response->getEffectiveUrl()->buildUrl());
    }
    public function testUserAgentLowerCase()
    {
        $client = $this->getHttpClient();
        $request = new Request('http://httpbin.org/get', 'GET');
        $request = $request->withHeader('user-agent', 'test-user-agent');
        $response = $client->sendRequest($request);
        $responseData = json_decode($response->getPageContent(), true);
        $this->assertEquals('test-user-agent', $responseData['headers']['User-Agent']);
    }
    public function testRedirectRequest()
    {
        $client = $this->getHttpClient();
        $request = new Request('http://httpbin.org/redirect-to?url=get', 'GET');
        $request = $request->withHeader('User-Agent', 'test-user-agent');
        $response = $client->sendRequest($request);
        $this->assertInstanceOf(SearchEngineResponse::class, $response);
        $responseData = json_decode($response->getPageContent(), true);
        $this->assertEquals(200, $response->getHttpResponseStatus());
        $this->assertEquals('test-user-agent', $responseData['headers']['User-Agent']);
        $this->assertEquals('http://httpbin.org/get', $response->getEffectiveUrl()->buildUrl());
        $this->assertEquals('http://httpbin.org/redirect-to?url=get', $response->getInitialUrl()->buildUrl());
    }
    public function testCookieEmpty()
    {
        $client = $this->getHttpClient();
        $request = new Request('http://httpbin.org/cookies', 'GET');
        $cookieJar = new ArrayCookieJar();
        $response = $client->sendRequest($request, null, $cookieJar);
        $responseData = json_decode($response->getPageContent(), true);
        $this->assertCount(0, $responseData['cookies']);
        $this->assertCount(0, $cookieJar->all());
    }
    public function testCookies()
    {
        $client = $this->getHttpClient();
        $request = new Request('http://httpbin.org/cookies', 'GET');
        $cookieJar = new ArrayCookieJar();
        $cookieJar->set(new Cookie('foo', 'bar', ['domain' => '.httpbin.org']));
        $cookieJar->set(new Cookie('bar', 'baz', ['domain' => '.foo.org']));
        $response = $client->sendRequest($request, null, $cookieJar);
        $responseData = json_decode($response->getPageContent(), true);
        $this->assertCount(1, $responseData['cookies']);
        $this->assertEquals(['foo' => 'bar'], $responseData['cookies']);
        $this->assertCount(2, $cookieJar->all());
    }
    public function testSetCookies()
    {
        $client = $this->getHttpClient();
        $request = new Request('http://httpbin.org/cookies/set?baz=bar', 'GET');
        $cookieJar = new ArrayCookieJar();
        $client->sendRequest($request, null, $cookieJar);
        $cookies = $cookieJar->all();
        $this->assertCount(1, $cookies);
        $this->assertEquals('baz', $cookies[0]->getName());
        $this->assertEquals('bar', $cookies[0]->getValue());
        $this->assertEquals('httpbin.org', $cookies[0]->getDomain());
    }
    public function testPostData(){
        $client = $this->getHttpClient();
        $request = new Request(
            'http://httpbin.org/post',
            'POST',
            'php://temp',
            [
                'User-Agent' => 'test-user-agent'
            ]
        );
        $request->getBody()->write('foo=bar');
        $response = $client->sendRequest($request);
        $this->assertInstanceOf(SearchEngineResponse::class, $response);
        $responseData = json_decode($response->getPageContent(), true);
        $this->assertEquals(200, $response->getHttpResponseStatus());
        $this->assertEquals('test-user-agent', $responseData['headers']['User-Agent']);
        $this->assertEquals('http://httpbin.org/post', $response->getEffectiveUrl()->buildUrl());

        $this->assertCount(1, $responseData['form']);
        $this->assertEquals('bar', $responseData['form']['foo']);
    }
}
