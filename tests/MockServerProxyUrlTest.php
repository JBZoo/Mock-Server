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

use JBZoo\HttpClient\Response;
use JBZoo\Utils\Str;

/**
 * Class ProxyUrlTest
 * @package JBZoo\PHPUnit
 */
class MockServerProxyUrlTest extends AbstractMockServerTest
{
    public function testPoxyUrlAllMethods(): void
    {
        $methods = [
            'GET',
            'POST',
            'PUT',
            'DELETE',
            'PATCH',
        ];

        foreach ($methods as $method) {
            $random = Str::random();

            $expectedResponse = $this->createClient()->request('http://httpbin.org/' . strtolower($method), [
                'query' => $random
            ], $method, ['headers' => ['X-Custom' => $random]]);

            $actualResponse = $this->request($method, ['query' => $random], self::TEST_URL, ['X-Custom' => $random]);

            $this->sameResponses($expectedResponse, $actualResponse);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param Response $expectedResponse
     * @param Response $actualResponse
     */
    private function sameResponses(Response $expectedResponse, Response $actualResponse): void
    {
        isNotSame(0, $expectedResponse->getCode(), $expectedResponse->getBody());
        isNotSame(0, $actualResponse->getCode(), $actualResponse->getBody());

        isNotSame(405, $expectedResponse->getCode(), $expectedResponse->getBody());
        isNotSame(405, $actualResponse->getCode(), $actualResponse->getBody());

        isSame($expectedResponse->getCode(), $actualResponse->getCode());
        $this->sameBody($expectedResponse->getJSON()->getArrayCopy(), $actualResponse->getJSON()->getArrayCopy());
        $this->sameHeaders($expectedResponse->getHeaders(), $actualResponse->getHeaders());
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
            'content-length',
            'x-amzn-trace-id',
            'date',
            'keep-alive',
        ];

        foreach ($excludeKeys as $excludeKey) {
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
        $excludeKeys = [
            'Accept',
            'Accept-Encoding',
            'Content-Type',
            'Host',
            'X-Amzn-Trace-Id',
            'Origin'
        ];

        foreach ($excludeKeys as $excludeKey) {
            unset(
                $expectedBody['headers'][$excludeKey],
                $actualBody['headers'][$excludeKey]
            );
        }

        unset($expectedBody['url'], $actualBody['url']);

        isSame($expectedBody, $actualBody, $message);
    }
}
