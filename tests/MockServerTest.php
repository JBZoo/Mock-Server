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

/**
 * Class MockServerTest
 * @package JBZoo\PHPUnit
 */
class MockServerTest extends AbstractMockServerTest
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
        isSame("./tests/mocks/{$this->getName()}.php", $response->getHeader('x-mock-server-fixture'));
        isTrue((int)$response->getHeader('x-mock-server-request-id') > 0);
    }

    public function testMultiplyMethods(): void
    {
        $formats = ['string', 'array'];
        $allowedMethods = ["GET", "POST", "PUT", "DELETE", "PATCH", "HEAD", /*'OPTIONS'*/];

        foreach ($formats as $format) {
            foreach ($allowedMethods as $allowedMethod) {
                $response = $this->request($allowedMethod, null, self::TEST_URL . '_' . $format);
                isSame(200, $response->getCode());

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

    public function testFunctionAsResponseBody(): void
    {
        $random = \random_int(1, 10000000);
        $response = $this->request(
            'POST',
            "test={$random}",
            self::TEST_URL . "?message={$random}",
            ['x-custom-header' => $random]
        );

        isSame(200, $response->getCode());
        isTrue($response->getJSON()->get('request_id') > 0);
        isSame([
            'uri'      => "http://{$this->testHost}/{$this->getName()}?message={$random}",
            'method'   => 'POST',
            'protocol' => '1.1',
            'headers'  => [
                'host'            => [$this->testHost],
                'x-custom-header' => [(string)$random],
                'user-agent'      => ['JBZoo/Http-Client (Guzzle)']
            ],
        ], $response->getJSON()->get('request'));
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

    /**
     * @depends testCustomDelay
     */
    public function testConcurrency(): void
    {
        $max = random_int(10, 100);

        $requests = [];
        for ($i = 0; $i < $max; $i++) {
            $requests[] = [$this->prepareUrl() . "?anti-cache={$i}"];
        }

        $start = microtime(true);
        $responses = $this->createClient()->multiRequest($requests);
        $time = (microtime(true) - $start) * 1000;

        isTrue($time > 1000 && $time < 1500, "Expected elapsedMS between 1000 & 1300, got: {$time}");

        $requestIds = [];
        foreach ($responses as $response) {
            $requestIds[] = $response->getJSON()->get('request_id');
        }

        isCount($max, array_unique($requestIds));
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

    public function testRequestParser(): void
    {
        //$random = \random_int(1, 10000000);
        $random = '1234567890';
        $response = $this->request(
            'POST',
            ['test' => $random],
            self::TEST_URL . "?message={$random}",
            ['x-custom-header' => $random]
        );

        dump($response->getJSON());
    }
}
