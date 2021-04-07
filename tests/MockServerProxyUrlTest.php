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
use JBZoo\HttpClient\Response;
use JBZoo\Utils\Str;

use function JBZoo\Data\json;

/**
 * Class ProxyUrlTest
 * @package JBZoo\PHPUnit
 */
class MockServerProxyUrlTest extends AbstractMockServerTest
{
    public function testPoxyUrlAllMethodsToHttpBin(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $random = Str::random();

            $expectedResponse = $this->createClient()->request('http://httpbin.org/' . strtolower($method), [
                'query' => $random
            ], $method, ['headers' => ['X-Custom' => $random]]);

            $actualResponse = $this->request($method, ['query' => $random], self::TEST_URL, ['X-Custom' => $random]);

            $this->sameResponses($expectedResponse, $actualResponse, "HTTP Method: {$method}");

            isSame($random, $expectedResponse->getJSON()->find('headers.X-Custom'));
        }
    }

    public function testPoxyUrlAllMethodsToSelf(): void
    {
        incomplete('Fix me');
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $random = Str::random();

            $url = $this->prepareUrl('testFunctionAsResponseBody');

            $expectedResponse = $this->createClient()->request(
                $url,
                ['query' => $random],
                $method,
                ['headers' => ['X-Custom' => $random]]
            );

            $actualResponse = $this->request($method, ['query' => $random], self::TEST_URL, ['X-Custom' => $random]);

            $this->sameResponses($expectedResponse, $actualResponse);

            isSame($random, $expectedResponse->getJSON()->find('headers.x-custom'));
        }
    }

    public function testPoxyUrlUploadFiles(): void
    {
        $exampleFile = file_get_contents(__DIR__ . '/mocks/Example.jpg');
        $files = [
            RequestOptions::MULTIPART => [
                ['name' => 'image_1', 'contents' => $exampleFile, 'filename' => 'Example_10.jpg'],
                ['name' => 'image_1', 'contents' => $exampleFile, 'filename' => 'Example_11.jpg'],
            ]
        ];

        $expectedResponse = json((new GuzzleHttpClient())
            ->request('POST', $this->prepareUrl(), $files)
            ->getBody()->getContents()
        )->getArrayCopy();

        $actualResponse = json((new GuzzleHttpClient())
            ->request('POST', 'http://httpbin.org/post', $files)
            ->getBody()->getContents()
        )->getArrayCopy();


        $this->sameBody($expectedResponse, $actualResponse);
    }

    /**
     * @depends testPoxyUrlUploadFiles
     */
    public function testPoxyUrlUploadFilesMemoryLeaks(): void
    {
        $exampleFile = file_get_contents(__DIR__ . '/mocks/Example_huge.jpg');
        $files = [
            RequestOptions::MULTIPART => [
                ['name' => 'image_1', 'contents' => $exampleFile, 'filename' => 'Example_10.jpg'],
            ]
        ];

        $expectedResponse = json((new GuzzleHttpClient())
            ->request('POST', $this->prepareUrl('testPoxyUrlUploadFiles'), $files)
            ->getBody()->getContents()
        )->getArrayCopy();

        $actualResponse = json((new GuzzleHttpClient())
            ->request('POST', 'http://httpbin.org/post', $files)
            ->getBody()->getContents()
        )->getArrayCopy();


        $this->sameBody($expectedResponse, $actualResponse);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param Response    $expectedResponse
     * @param Response    $actualResponse
     * @param string|null $message
     */
    private function sameResponses(Response $expectedResponse, Response $actualResponse, ?string $message = null): void
    {
        isNotSame(0, $expectedResponse->getCode(), $expectedResponse->getBody());
        isNotSame(0, $actualResponse->getCode(), $actualResponse->getBody());

        isNotSame(405, $expectedResponse->getCode(), $expectedResponse->getBody());
        isNotSame(405, $actualResponse->getCode(), $actualResponse->getBody());

        isSame($expectedResponse->getCode(), $actualResponse->getCode(), $message);
        $this->sameBody(
            $expectedResponse->getJSON()->getArrayCopy(),
            $actualResponse->getJSON()->getArrayCopy(),
            $message
        );

        $this->sameHeaders($expectedResponse->getHeaders(), $actualResponse->getHeaders(), $message);
    }

    /**
     * @param array       $expectedHeaders
     * @param array       $actualHeaders
     * @param string|null $message
     */
    private function sameHeaders(array $expectedHeaders, array $actualHeaders, string $message = ''): void
    {
        $expectedHeaders = array_change_key_case($expectedHeaders);
        $actualHeaders = array_change_key_case($actualHeaders);

        $excludeKeys = [
            'vary',
            'content-length',
            'x-amzn-trace-id',
            'date',
            'keep-alive',
            'x-mock-server-request-id',
            'x-mock-server-fixture',
        ];

        foreach ($excludeKeys as $excludeKey) {
            if (isset($expectedHeaders[$excludeKey])) {
                isNotEmpty($expectedHeaders[$excludeKey]);
                isNotEmpty($actualHeaders[$excludeKey]);
            }

            unset(
                $expectedHeaders[$excludeKey],
                $actualHeaders[$excludeKey]
            );
        }

        ksort($expectedHeaders);
        ksort($actualHeaders);

        isSame($expectedHeaders, $actualHeaders, $message);
    }

    /**
     * @param array       $expectedBody
     * @param array       $actualBody
     * @param string|null $message
     */
    private function sameBody(array $expectedBody, array $actualBody, string $message = ''): void
    {
        $excludeHeaderKeys = [
            'Accept',
            'Accept-Encoding',
            'Content-Length',
            'Content-Type',
            'Host',
            'X-Amzn-Trace-Id',
            'Origin'
        ];

        foreach ($excludeHeaderKeys as $excludeHeaderKey) {
            $excludeHeaderKeyLower = strtolower($excludeHeaderKey);

            if (isset($expectedBody[$excludeHeaderKey])) {
                isNotEmpty($expectedBody[$excludeHeaderKey]);
                isNotEmpty($actualBody[$excludeHeaderKey]);
            }

            if (isset($expectedBody[$excludeHeaderKeyLower])) {
                isNotEmpty($expectedBody[$excludeHeaderKeyLower]);
                isNotEmpty($actualBody[$excludeHeaderKeyLower]);
            }

            unset(
                $expectedBody['headers'][$excludeHeaderKey],
                $actualBody['headers'][$excludeHeaderKey],
                $expectedBody['headers'][$excludeHeaderKeyLower],
                $actualBody['headers'][$excludeHeaderKeyLower]
            );
        }

        $excludeBodyKeys = [
            'id',
            'url',
            'origin'
        ];

        foreach ($excludeBodyKeys as $excludeBodyKey) {
            unset($expectedBody[$excludeBodyKey], $actualBody[$excludeBodyKey]);
        }

        isSame($expectedBody, $actualBody, $message);
    }
}
