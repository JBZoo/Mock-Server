<?php

/**
 * JBZoo Toolbox - Mock-Server.
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @see        https://github.com/JBZoo/Mock-Server
 */

declare(strict_types=1);

namespace JBZoo\PHPUnit;

use JBZoo\HttpClient\HttpClient;
use JBZoo\HttpClient\Response;
use JBZoo\MockServer\Server\MockServer;

abstract class AbstractMockServerTest extends PHPUnit
{
    protected const TEST_URL = '__SELF__';

    protected const DEFAULT_HTTP_OPTIONS = [
        'exceptions'      => false,
        'allow_redirects' => false,
        'max_redirects'   => 2,
        'timeout'         => 20,
    ];

    /** @var string */
    protected $testHost;

    protected function setUp(): void
    {
        $this->testHost = MockServer::DEFAULT_HOST . ':' . MockServer::DEFAULT_PORT;
        parent::setUp();
    }

    /**
     * @param null|array|string $args
     */
    protected function request(
        string $method = 'GET',
        $args = null,
        string $relativePath = self::TEST_URL,
        array $headers = [],
    ): Response {
        $client = $this->createClient();

        return $client->request($this->prepareUrl($relativePath), $args, $method, ['headers' => $headers]);
    }

    protected function createClient(array $options = self::DEFAULT_HTTP_OPTIONS): HttpClient
    {
        return new HttpClient($options);
    }

    protected function prepareUrl(string $relativePath = self::TEST_URL): string
    {
        return \str_replace(self::TEST_URL, $this->getName(), "http://{$this->testHost}/{$relativePath}");
    }
}
