<?php

namespace Piwik\ReportingApi\tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Piwik\ReportingApi\HttpClient;
use Piwik\ReportingApi\RequestFactoryInterface;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;

/**
 * Tests for the HttpClient class.
 *
 * @coversDefaultClass \Piwik\ReportingApi\HttpClient
 */
class HttpClientTest extends TestCase
{

    /**
     * The query factory object.
     *
     * @var \Piwik\ReportingApi\HttpClient
     */
    protected $httpClient;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        // This sets up the mock client to respond to the first request it gets
        // with an HTTP 200 containing your mock json body.
        $mock = new MockHandler([new Response(200, [], 'NA')]);
        $handler = HandlerStack::create($mock);
        $mockHttp = new Client(['handler' => $handler]);
        $requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $this->httpClient = new HttpClient($mockHttp, $requestFactory->reveal());
    }

    /**
     * Tests the getters and setters of the class.
     */
    public function testArguments()
    {
        $params = ['foo' => 'bar'];
        $this->httpClient->setRequestParams($params);
        $this->assertEquals($params, $this->httpClient->getRequestParams());
    }

    /**
     * @param string $url
     *   A valid URL.
     *
     * @covers ::getUrl
     * @dataProvider urlProvider
     */
    public function testGetUrl($url)
    {
        $http_client = $this->getHttpClient();

        // By default the URL is empty.
        $this->assertEmpty($http_client->getUrl());

        // Check that any URL that is set is correctly returned.
        $http_client->setUrl($url);
        $this->assertEquals($url, $http_client->getUrl());
    }

    /**
     * @param string $url
     *   A valid URL.
     *
     * @covers ::setUrl
     * @dataProvider urlProvider
     */
    public function testSetUrl($url)
    {
        $http_client = $this->getHttpClient();

        // Check that the object itself is returned for chaining.
        $result = $http_client->setUrl($url);
        $this->assertEquals($http_client, $result);

        // Check that the URL has been correctly set.
        $this->assertEquals($url, $http_client->getUrl());
    }

    /**
     * @param string $invalid_url
     *   An invalid URL.
     *
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidUrlProvider
     */
    public function testInvalidUrl($invalid_url)
    {
        $this->getHttpClient()->setUrl($invalid_url);
    }

    /**
     * Data provider returning valid URLs.
     */
    public function urlProvider()
    {
        return [
            ['http://example.com'],
            ['http://exa-mple.com/pa-th/'],
            ['https://archive.example.net/'],
            ['https://archive.example.net./'],
            ['http://127.0.0.1'],
            ['http://[2001:0db8:0000:85a3:0000:0000:ac1f:8001]'],
            ['ftp://a.b.com'],
            ['http://localhost/'],
            ['irc://user:password@localhost:8000/path'],
            ['irc://:@localhost:'],
        ];
    }

    /**
     * Data provider returning invalid URLs.
     */
    public function invalidUrlProvider()
    {
        return [
            ['http://exa_mple.com'],
            ['archive.example.net'],
            ['irc://:@:/path'],
            ['http://..com'],
            ['/'],
            [null],
            [true],
            [false],
            [''],
            [[]],
            [-1],
            [0],
            [1],
            [2e-2],
        ];
    }

    /**
     * @param string $method
     *   A supported HTTP method.
     *
     * @covers ::getMethod
     * @dataProvider supportedHttpMethodsProvider
     */
    public function testGetMethod($method)
    {
        $http_client = $this->getHttpClient();

        // The GET method should be set by default.
        $this->assertEquals('GET', $http_client->getMethod());

        // Check that the method that was set is returned correctly.
        $http_client->setMethod($method);
        $this->assertEquals($method, $http_client->getMethod());
    }

    /**
     * @param string $method
     *   A supported HTTP method.
     *
     * @covers ::getMethod
     * @dataProvider supportedHttpMethodsProvider
     */
    public function testSetMethod($method)
    {
        // It is expected that the given HTTP method will be passed to the request factory.
        $request_factory = $this->prophesize(RequestFactoryInterface::class);
        $request_factory
            ->getRequest($method, Argument::any())
            ->willReturn($this->prophesize(RequestInterface::class)->reveal())
            ->shouldBeCalled();

        $http_client = $this->getHttpClient(null, $request_factory->reveal());
        $http_client->setUrl('http://example.com');
        $result = $http_client->setMethod($method);
        // Send the request so that we can check if the correct HTTP method is used.
        $http_client->sendRequest();

        // Check that the client is returned for chaining.
        $this->assertEquals($http_client, $result);
    }

    /**
     * Tests that an exception is thrown for unsupported HTTP methods.
     *
     * @param string $invalid_method
     *   An unsupported or invalid HTTP method.
     *
     * @covers ::setMethod
     * @expectedException \InvalidArgumentException
     * @dataProvider unsupportedHttpMethodsProvider
     */
    public function testUnsupportedHttpMethods($invalid_method)
    {
        $http_client = $this->getHttpClient();
        $http_client->setMethod($invalid_method);
    }

    /**
     * Data provider returning supported HTTP methods.
     */
    public function supportedHttpMethodsProvider()
    {
        return [
            ['GET'],
            ['POST'],
        ];
    }

    /**
     * Data provider returning unsupported and invalid HTTP methods.
     */
    public function unsupportedHttpMethodsProvider()
    {
        return [
            // Unsupported HTTP methods.
            ['CONNECT'],
            ['DELETE'],
            ['HEAD'],
            ['OPTIONS'],
            ['PUT'],
            ['TRACE'],
            // Lowercase HTTP methods are invalid.
            ['connect'],
            ['delete'],
            ['get'],
            ['head'],
            ['options'],
            ['post'],
            ['put'],
            ['trace'],
            // Some arguments that are no HTTP methods.
            [null],
            [true],
            [false],
            [''],
            [0],
            [1],
            [-1],
            ['0'],
        ];
    }

    /**
     * Tests the execute method.
     */
    public function testExecute()
    {
        $http_client = $this->getHttpClient();
        $this->expectException(\Exception::class);
        $http_client
          ->setRequestParams(['foo' => 'bar'])
          ->setMethod('GET')
          ->sendRequest();

        $return = $http_client
          ->setUrl('http://example.com')
          ->setRequestParams(['foo' => 'bar'])
          ->setMethod('GET')
          ->sendRequest();

        $this->assertTrue($return instanceof Response);
    }

    /**
     * Returns the SUT.
     *
     * @return \Piwik\ReportingApi\HttpClient
     *   The class being tested.
     */
    protected function getHttpClient(ClientInterface $httpClient = null, RequestFactoryInterface $requestFactory = null)
    {
        $httpClient = $httpClient ?: $this->prophesize(ClientInterface::class)->reveal();
        $requestFactory = $requestFactory ?: $this->prophesize(RequestFactoryInterface::class)->reveal();
        return new HttpClient($httpClient, $requestFactory);
    }
}
