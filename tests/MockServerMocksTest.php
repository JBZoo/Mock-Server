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

use JBZoo\MockServer\Mocks\JsonMock;
use JBZoo\MockServer\Mocks\PhpMock;
use JBZoo\MockServer\Mocks\YmlMock;

/**
 * Class MockServerMocksTest
 * @package JBZoo\PHPUnit
 */
class MockServerMocksTest extends AbstractMockServerTest
{
    public function testStaticPhp(): void
    {
        $phpMock = __DIR__ . '/../tests/mocks/max/testStaticPhp.php';

        $mock = new PhpMock($phpMock);

        isSame("{\n    \"result\": \"ok\"\n}", $mock->getResponseBody());
        isSame(200, $mock->getResponseCode());
        isSame([
            'X-Mock-Server-Fixture'    => $phpMock,
            'X-Mock-Server-Request-Id' => null,
            'Content-Type'             => 'application/json'
        ], $mock->getResponseHeaders());

        isSame('/testStaticPhp', $mock->getRequestPath());
        isSame(['GET', 'POST', 'PUT', 'PATCH', 'HEAD', 'OPTIONS', 'DELETE'], $mock->getRequestMethods());
        isSame([], $mock->getRequestHeader());

        isSame(false, $mock->isCrazyEnabled());
        isSame(1000, $mock->getDelay());
    }

    public function testDynamicPhp(): void
    {
        $phpMock = __DIR__ . '/../tests/mocks/max/testDynamicPhp.php';
        $mock = new PhpMock($phpMock);

        $mock->bindRequest();

        isSame("{\n    \"result\": \"ok\"\n}", $mock->getResponseBody());
        isSame(200, $mock->getResponseCode());
        isSame([
            'X-Mock-Server-Fixture'    => $phpMock,
            'X-Mock-Server-Request-Id' => null,
            'Content-Type'             => 'application/json'
        ], $mock->getResponseHeaders());

        isSame('/testDynamicPhp', $mock->getRequestPath());
        isSame(['GET', 'POST'], $mock->getRequestMethods());
        isSame([], $mock->getRequestHeader());

        isSame(false, $mock->isCrazyEnabled());
        isSame(1000, $mock->getDelay());
    }

    public function testStaticYml(): void
    {
        $ymlMock = __DIR__ . '/../tests/mocks/max/testStaticYml.yml';

        $mock = new YmlMock($ymlMock);

        isSame("{\n    \"result\": \"ok\"\n}", $mock->getResponseBody());
        isSame(200, $mock->getResponseCode());
        isSame([
            'X-Mock-Server-Fixture'    => $ymlMock,
            'X-Mock-Server-Request-Id' => null,
            'Content-Type'             => 'application/json'
        ], $mock->getResponseHeaders());

        isSame('/testStaticYml', $mock->getRequestPath());
        isSame(['GET', 'POST', 'PUT', 'PATCH', 'HEAD', 'OPTIONS', 'DELETE'], $mock->getRequestMethods());
        isSame([], $mock->getRequestHeader());

        isSame(false, $mock->isCrazyEnabled());
        isSame(1000, $mock->getDelay());
    }

    public function testStaticJson(): void
    {
        $jsonMock = __DIR__ . '/../tests/mocks/max/testStaticJson.json';

        $mock = new JsonMock($jsonMock);

        isSame("{\n    \"result\": \"ok\"\n}", $mock->getResponseBody());
        isSame(200, $mock->getResponseCode());
        isSame([
            'X-Mock-Server-Fixture'    => $jsonMock,
            'X-Mock-Server-Request-Id' => null,
            'Content-Type'             => 'application/json'
        ], $mock->getResponseHeaders());

        isSame('/testStaticJson', $mock->getRequestPath());
        isSame(['GET', 'POST', 'PUT', 'PATCH', 'HEAD', 'OPTIONS', 'DELETE'], $mock->getRequestMethods());
        isSame([], $mock->getRequestHeader());

        isSame(false, $mock->isCrazyEnabled());
        isSame(1000, $mock->getDelay());
    }
}
