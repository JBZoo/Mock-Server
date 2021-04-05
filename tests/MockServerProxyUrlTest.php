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
    public function testPoxyUrlGet(): void
    {
        $random = Str::random();

        $expectedResponse = $this->createClient()->request('https://httpbin.org/get', ['query' => $random]);


        $actualResponse = $this->request('GET', ['query' => $random]);
        dump($actualResponse);

        $this->sameResponses($expectedResponse, $actualResponse);
    }

    public function testPoxyUrlPostComplex(): void
    {
        $random = Str::random();

        $expectedResponse = $this->createClient()->request('https://httpbin.org/post', ['data' => $random]);
        $actualResponse = $this->request('POST', ['data' => $random]);

        $this->sameResponses($expectedResponse, $actualResponse);
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

        $this->sameBody(
            $expectedResponse->getJSON()->getArrayCopy(),
            $actualResponse->getJSON()->getArrayCopy()
        );

        $this->sameHeaders(
            $expectedResponse->getHeaders(),
            $actualResponse->getHeaders()
        );
    }

    /**
     * @param array       $expected
     * @param array       $actual
     * @param string|null $message
     */
    private function sameHeaders(array $expected, array $actual, ?string $message = null): void
    {
        $expected = array_change_key_case($expected);
        $actual = array_change_key_case($actual);

        unset(
            $expected['x-amzn-trace-id'],
            $actual['x-amzn-trace-id'],
            $expected['date'],
            $actual['date'],
            $expected['keep-alive'],
            $actual['keep-alive']
        );
        ksort($expected);
        ksort($actual);

        isSame($expected, $actual, $message);
    }

    /**
     * @param array       $expected
     * @param array       $actual
     * @param string|null $message
     */
    private function sameBody(array $expected, array $actual, ?string $message = null): void
    {
        unset($expected['headers']['X-Amzn-Trace-Id'], $actual['headers']['X-Amzn-Trace-Id']);
        isSame($expected, $actual, $message);
    }
}
