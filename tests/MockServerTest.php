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

namespace JBZoo\PHPUnit;

use JBZoo\HttpClient\HttpClient;
use JBZoo\HttpClient\Response;
use JBZoo\MockServer\Application;

/**
 * Class MockServerTest
 * @package JBZoo\PHPUnit
 */
class MockServerTest extends PHPUnit
{
    private const TEST_URL = '__SELF__';

    /**
     * @var string
     */
    private $testHost;

    protected function setUp(): void
    {
        $this->testHost = Application::DEFAULT_HOST . ':' . Application::DEFAULT_PORT;
        parent::setUp();
    }

    /**
     * @param string            $relativePath
     * @param array|string|null $args
     * @param string            $method
     * @param array             $headers
     * @return Response
     */
    private function request(
        string $method = 'GET',
        $args = null,
        string $relativePath = self::TEST_URL,
        array $headers = []
    ) {
        $client = new HttpClient(['exceptions' => false, 'allow_redirects' => false]);
        $url = str_replace(self::TEST_URL, $this->getName(), "http://{$this->testHost}/{$relativePath}");
        return $client->request($url, $args, $method, ['headers' => $headers]);
    }

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
        $response = $this->request();
        isSame(200, $response->getCode());
        isSame('Hello', $response->getJSON()->get('message'));

        $response = $this->request('POST');
        isSame(200, $response->getCode());
        isSame('Hello', $response->getJSON()->get('message'));
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

    public function testFunctionAsBody(): void
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

    public function testFakerAsPartOfBody(): void
    {
        isNotSame($this->request()->getJSON()->get('name'), $this->request()->getJSON()->get('name'));
        isNotSame($this->request()->getJSON()->get('name'), $this->request()->getJSON()->get('name'));
    }
}
