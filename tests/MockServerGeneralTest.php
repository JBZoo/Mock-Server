<?php

/**
 * JBZoo Toolbox - Mock-Server
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Mock-Server
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/Mock-Server
 */

declare(strict_types=1);

namespace JBZoo\PHPUnit;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\RequestOptions;
use JBZoo\MockServer\Server\MockServer;
use JBZoo\Utils\Str;

use function JBZoo\Data\json;

/**
 * Class MockServerTest
 * @package JBZoo\PHPUnit
 */
class MockServerGeneralTest extends AbstractMockServerTest
{
    public function testMinimalMock(): void
    {
        $response = $this->request();
        isSame(200, $response->getCode());
        isSame('', $response->getBody());
    }

    public function testStructureOfResponse(): void
    {
        $response = $this->request();
        isSame(200, $response->getCode());
        isSame('Hello', $response->getJSON()->get('message'));
        isSame('application/json', $response->getHeader('content-type'));
        isContain("tests/mocks/php/General/{$this->getName()}.php", $response->getHeader('x-mock-server-fixture'));
        isTrue((int)$response->getHeader('x-mock-server-request-id') > 0);
    }

    public function testMultiplyMethods(): void
    {
        $formats = ['string', 'array', 'star', 'any'];
        $allowedMethods = ["GET", "POST", "PUT", "DELETE", "PATCH", "HEAD", /*'OPTIONS'*/];

        foreach ($formats as $format) {
            foreach ($allowedMethods as $allowedMethod) {
                $response = $this->request($allowedMethod, null, self::TEST_URL . '_' . $format);
                isSame(200, $response->getCode(), "Format:{$format}; Method:{$allowedMethod}");

                if ($allowedMethod === 'HEAD') {
                    isSame('', $response->getBody());
                } else {
                    isSame($allowedMethod, $response->getJSON()->get('method'));
                }
            }
        }
    }

    public function testRedirect(): void
    {
        $response = $this->request();
        isSame(301, $response->getCode());
        isSame('redirect', $response->getJSON()->get('message'));
    }

    public function testNotFound(): void
    {
        $response = $this->request();
        isSame(404, $response->getCode());
        isSame('not_found', $response->getJSON()->get('message'));
    }

    public function testServerError(): void
    {
        $response = $this->request();
        isSame(500, $response->getCode());
        isSame('fatal_error', $response->getJSON()->get('message'));
    }

    public function testUndefinedRoute(): void
    {
        $path = 'undefined-route-' . Str::random();
        $response = $this->request('GET', null, $path);

        isSame(500, $response->getCode());
        isSame([
            'fatal_error' => '500 Route not found or something went wrong. See server logs.',
            'request'     => [
                'uri'     => "http://0.0.0.0:8089/{$path}",
                'headers' => [
                    'host'       => ['0.0.0.0:8089'],
                    'user-agent' => ['JBZoo/Http-Client (Guzzle)']
                ]
            ]
        ], $response->getJSON()->getArrayCopy());
    }

    public function testExceptionInMock(): void
    {
        $response = $this->request();

        isSame(500, $response->getCode());
        isSame([
            'fatal_error' => '500 Route not found or something went wrong. See server logs.',
            'request'     => [
                'uri'     => 'http://0.0.0.0:8089/testExceptionInMock',
                'headers' => [
                    'host'       => ['0.0.0.0:8089'],
                    'user-agent' => ['JBZoo/Http-Client (Guzzle)']
                ]
            ]
        ], $response->getJSON()->getArrayCopy());
    }

    public function testFunctionAsResponseBody(): void
    {
        $random = (string)\random_int(1000, 9999);

        // test GET, no params
        $resultGet = $this->request()->getJSON()->getArrayCopy();
        isTrue($resultGet['id'] > 0);
        unset($resultGet['id']);
        isSame([
            'protocol'       => '1.1',
            'method'         => 'GET',
            'headers'        => [
                'host'       => '0.0.0.0:8089',
                'user-agent' => 'JBZoo/Http-Client (Guzzle)'
            ],
            'cookies'        => [],
            'user_agent'     => 'JBZoo/Http-Client (Guzzle)',
            'client_ip'      => '127.0.0.1',
            'uri'            => [
                'full'      => 'http://0.0.0.0:8089/testFunctionAsResponseBody',
                'scheme'    => 'http',
                'host'      => '0.0.0.0',
                'port'      => 8089,
                'authority' => '0.0.0.0:8089',
                'path'      => '/testFunctionAsResponseBody',
                'query'     => '',
                'user_info' => ''
            ],
            'params'         => [
                'query' => [],
                'body'  => [],
                'all'   => [],
            ],
            'uploaded_files' => [],
        ], $resultGet);

        // test POST + complex request
        $responsePost = $this->request(
            'POST',
            [
                'test'  => $random,
                'array' => ['value_1' => $random, 'nested' => [$random, $random]]
            ],
            self::TEST_URL . "?test={$random}&array[]=123456&array[]=654987&message=123",
            [
                'Cookie'          => 'PHPSESSID=poiuytrewq; RMT=qwerty123',
                'x-custom-header' => $random
            ]
        )->getJSON()->getArrayCopy();

        isTrue($responsePost['id'] > 0);
        unset($responsePost['id']);
        isSame([
            'protocol'       => '1.1',
            'method'         => 'POST',
            'headers'        => [
                'host'            => '0.0.0.0:8089',
                'content-type'    => 'application/x-www-form-urlencoded',
                'cookie'          => 'PHPSESSID=poiuytrewq; RMT=qwerty123',
                'x-custom-header' => $random,
                'user-agent'      => 'JBZoo/Http-Client (Guzzle)',
                'content-length'  => '93'
            ],
            'cookies'        => ['PHPSESSID' => 'poiuytrewq', 'RMT' => 'qwerty123'],
            'user_agent'     => 'JBZoo/Http-Client (Guzzle)',
            'client_ip'      => '127.0.0.1',
            'uri'            => [
                'full'      => 'http://0.0.0.0:8089/testFunctionAsResponseBody?' .
                    "test={$random}&array%5B%5D=123456&array%5B%5D=654987&message=123",
                'scheme'    => 'http',
                'host'      => '0.0.0.0',
                'port'      => 8089,
                'authority' => '0.0.0.0:8089',
                'path'      => '/testFunctionAsResponseBody',
                'query'     => "test={$random}&array%5B%5D=123456&array%5B%5D=654987&message=123",
                'user_info' => ''
            ],
            'params'         => [
                'query' => ['test' => $random, 'array' => ['123456', '654987'], 'message' => '123'],
                'body'  => ['test' => $random, 'array' => ['value_1' => $random, 'nested' => [$random, $random]]],
                'all'   => [
                    'test'      => $random,
                    'array'     => ['value_1' => $random, 'nested' => [$random, $random]],
                    'message'   => '123',
                    'PHPSESSID' => 'poiuytrewq',
                    'RMT'       => 'qwerty123'
                ]
            ],
            "uploaded_files" => []
        ], $responsePost);

        // Upload file in POST
        $exampleFile = file_get_contents(__DIR__ . '/mocks/Example.jpg');
        $responseUpload = (new GuzzleHttpClient())->request('POST', $this->prepareUrl(), [
            RequestOptions::MULTIPART => [
                ['name' => 'image_1', 'contents' => $exampleFile, 'filename' => 'Example_10.jpg'],
                ['name' => 'image_1', 'contents' => $exampleFile, 'filename' => 'Example_11.jpg'],
                ['name' => 'image_2', 'contents' => $exampleFile, 'filename' => 'Example_20.jpg']
            ]
        ]);

        $responseUpload = json($responseUpload->getBody()->getContents())->getArrayCopy();
        isTrue($responseUpload['id'] > 0);
        unset($responseUpload['id']);
        isContain('multipart/form-data; boundary=', $responseUpload['headers']['content-type']);
        unset($responseUpload['headers']['content-type']);
        isSame([
            'protocol'       => '1.1',
            'method'         => 'POST',
            'headers'        => [
                'host'           => '0.0.0.0:8089',
                'user-agent'     => 'GuzzleHttp/7',
                'content-length' => '27625'
            ],
            'cookies'        => [],
            'user_agent'     => 'GuzzleHttp/7',
            'client_ip'      => '127.0.0.1',
            'uri'            => [
                'full'      => 'http://0.0.0.0:8089/testFunctionAsResponseBody',
                'scheme'    => 'http',
                'host'      => '0.0.0.0',
                'port'      => 8089,
                'authority' => '0.0.0.0:8089',
                'path'      => '/testFunctionAsResponseBody',
                'query'     => '',
                'user_info' => ''
            ],
            'params'         => [
                'query' => [],
                'body'  => [],
                'all'   => []
            ],
            'uploaded_files' => [
                'image_1' => [
                    ['name' => 'Example_10.jpg', 'mime' => 'image/jpeg', 'contents' => base64_encode($exampleFile)],
                    ['name' => 'Example_11.jpg', 'mime' => 'image/jpeg', 'contents' => base64_encode($exampleFile)],
                ],
                'image_2' => [
                    ['name' => 'Example_20.jpg', 'mime' => 'image/jpeg', 'contents' => base64_encode($exampleFile)]
                ]
            ]
        ], $responseUpload);
    }

    public function testFunctionAsResponseHeaders(): void
    {
        isNotSame($this->request()->getHeader('x-random-value'), $this->request()->getHeader('x-random-value'));
    }

    public function testFunctionAsResponseCode(): void
    {
        isSame(200, $this->request('PUT')->getCode());
        isSame(404, $this->request('POST')->getCode());
    }

    public function testFakerAsPartOfBody(): void
    {
        isNotSame($this->request()->getJSON()->get('name'), $this->request()->getJSON()->get('name'));
        isNotSame($this->request()->getJSON()->get('name'), $this->request()->getJSON()->get('name'));
    }

    public function testConcurrency(): void
    {
        $maxRequestsAtOnce = random_int(10, 100);
        //$maxRequestsAtOnce = 2;

        $requests = [];
        for ($i = 0; $i < $maxRequestsAtOnce; $i++) {
            $requests[] = [$this->prepareUrl(), ['anti-cache' => $i]];
        }

        $start = microtime(true);
        $responses = $this->createClient()->multiRequest($requests);
        $time = (microtime(true) - $start) * 1000;

        isTrue($time > 1000 && $time < 1600, "Expected elapsedMS between 1000 & 1600, got: {$time}");

        $requestIds = [];
        foreach ($responses as $response) {
            $requestIds[] = $response->getJSON()->get('request_id');
        }

        isCount($maxRequestsAtOnce, array_unique($requestIds));
    }

    /**
     * Just in case we have to warm up the server and PhpUnit framework
     * @depends testMinimalMock
     */
    public function testCustomDelay(): void
    {
        $start = microtime(true);
        $response = $this->request();
        $time = (microtime(true) - $start) * 1000;

        isAmount($response->getTime() * 1000, $time, '', 100);

        isTrue($time > 1000 && $time < 1300, "Expected elapsedMS between 1000 & 1300, got: {$time}");
    }

    /**
     * Just in case we have to warm up the server and PhpUnit framework
     * @depends testMinimalMock
     */
    public function testFunctionAsDelay(): void
    {
        $start = microtime(true);
        $response = $this->request();
        $time = (microtime(true) - $start) * 1000;

        isAmount($response->getTime() * 1000, $time, '', 100);
        isTrue($time > 1000 && $time < 1300, "Expected elapsedMS between 1000 & 1300, got: {$time}");
    }

    public function testFileAsBody(): void
    {
        $response = $this->request();
        isSame($response->getBody(), file_get_contents(__DIR__ . '/mocks/Example.jpg'));
        isSame('image/jpeg', $response->getHeader('Content-Type'));
    }

    /**
     * @depends testConcurrency
     */
    public function testFunctionAsCrazy(): void
    {
        $methods = ['GET', 'DELETE']; // GET works in crazy mode
        $maxRequestsAtOnce = 10;

        foreach ($methods as $method) {
            $requests = [];
            for ($i = 0; $i < $maxRequestsAtOnce; $i++) {
                $requests[] = [$this->prepareUrl(), null, $method];
            }

            $responses = $this->createClient()->multiRequest($requests);

            $requestIds = [];
            foreach ($responses as $response) {
                $headers = $response->getHeaders();
                // remove random values to make predictable hash
                unset($headers['content-length'], $headers['x-mock-server-request-id'], $headers['date']);

                $requestIds[] = sha1(serialize([$response->getCode(), $response->getBody(), $headers]));
            }

            if ($method === 'GET') {
                isTrue(count(array_unique($requestIds)) > 1);
            } else {
                isCount(1, array_unique($requestIds));
            }
        }
    }

    public function testCrazy(): void
    {
        $maxRequestsAtOnce = 10;

        $requests = [];
        for ($i = 0; $i < $maxRequestsAtOnce; $i++) {
            $requests[] = [$this->prepareUrl()];
        }

        $responses = $this->createClient()->multiRequest($requests);

        $requestIds = [];
        foreach ($responses as $response) {
            $headers = $response->getHeaders();
            // remove random values to make predictable hash
            unset($headers['content-length'], $headers['x-mock-server-request-id'], $headers['date']);

            $requestIds[] = sha1(serialize([$response->getCode(), $response->getBody(), $headers]));
        }

        isTrue(count(array_unique($requestIds)) > 1);
    }

    public function testTlsConnection(): void
    {
        incomplete('fix me');
        $tlsHost = 'localhost:' . MockServer::DEFAULT_PORT_TLS;

        $client = new \GuzzleHttp\Client(['base_uri' => "https://{$tlsHost}"]);
        $response = $client->request('GET', '/testTlsConnection', [
            'verify' => __DIR__ . '/../vendor/amphp/http-server/tools/tls/localhost.pem',
        ]);

        isSame(200, $response->getStatusCode());
        isSame('Hi', $response->getBody()->getContents());
    }

    public function testStaticYml(): void
    {
        $response = $this->request();
        isSame(200, $response->getCode());
        isSame('ok', $response->getJSON()->get('result'));
    }
}
