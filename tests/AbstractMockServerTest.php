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
 * Class AbstractMockServerTest
 * @package JBZoo\PHPUnit
 */
abstract class AbstractMockServerTest extends PHPUnit
{
    protected const TEST_URL = '__SELF__';

    protected const DEFAULT_HTTP_OPTIONS = [
        'exceptions'      => false,
        'allow_redirects' => false,
        'max_redirects'   => 2,
    ];

    /**
     * @var string
     */
    protected $testHost;

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
    protected function request(
        string $method = 'GET',
        $args = null,
        string $relativePath = self::TEST_URL,
        array $headers = []
    ): Response {
        $client = $this->createClient();
        return $client->request($this->prepareUrl($relativePath), $args, $method, ['headers' => $headers]);
    }

    /**
     * @param array $options
     * @return HttpClient
     */
    protected function createClient(array $options = self::DEFAULT_HTTP_OPTIONS): HttpClient
    {
        return new HttpClient($options);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    protected function prepareUrl(string $relativePath = self::TEST_URL): string
    {
        return str_replace(self::TEST_URL, $this->getName(), "http://{$this->testHost}/{$relativePath}");
    }
}
