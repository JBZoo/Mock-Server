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

use function JBZoo\Data\json;

/**
 * Class MockServerWebhooksTest
 * @package JBZoo\PHPUnit
 */
class MockServerWebhooksTest extends AbstractMockServerTest
{
    /**
     * @return string
     */
    public static function getCallback(): string
    {
        return 'http://0.0.0.0:8089/testWebHooksCallback';
    }

    /**
     * @return string
     */
    public static function getDumpPath(): string
    {
        return __DIR__ . '/../build/testWebHooksCallback.json';
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (file_exists(self::getDumpPath())) {
            unlink(self::getDumpPath());
        }
    }

    public function testWebHooksBasic(): void
    {
        $response = $this->request();
        isSame('ok', $response->getJSON()->get('result'));

        sleep(1);

        $this->assertCallback([
            'protocol'       => '1.1',
            'method'         => 'GET',
            'headers'        => ['user-agent' => 'Mock-Server Webhook'],
            'cookies'        => [],
            'client_ip'      => '127.0.0.1',
            'uri'            => self::getCallback(),
            'params_query'   => [],
            'params_body'    => [],
            'uploaded_files' => []
        ]);
    }

    public function testWebHooksCallbackDump(): void
    {
        $response = $this->request('POST', null, 'testWebHooksCallback?arg=123');
        isSame('ok', $response->getBody());

        $this->assertCallback([
            'protocol'       => '1.1',
            'method'         => 'POST',
            'headers'        => ['user-agent' => 'JBZoo/Http-Client (Guzzle)'],
            'cookies'        => [],
            'client_ip'      => '127.0.0.1',
            'uri'            => self::getCallback() . '?arg=123',
            'params_query'   => ['arg' => '123'],
            'params_body'    => [],
            'uploaded_files' => []
        ]);
    }

    /**
     * @param array $expectedDump
     */
    private function assertCallback(array $expectedDump): void
    {
        isFile(self::getDumpPath());
        $actualDump = json(self::getDumpPath())->getArrayCopy();
        isNotEmpty($actualDump);

        isSame($expectedDump, $actualDump);
    }
}
