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

use JBZoo\MockServer\Server\Request;
use JBZoo\PHPUnit\MockServerWebhooksTest;

use function JBZoo\Data\json;

return [
    'request' => [
        'method' => '*',
        'path'   => '/' . pathinfo(__FILE__, PATHINFO_FILENAME)
    ],

    'response' => [
        'code' => 200,
        'body' => static function (Request $request): string {
            dump(1);
            file_put_contents(MockServerWebhooksTest::getDumpPath(), (string)json([
                'protocol'       => $request->getProtocolVersion(),
                'method'         => $request->getMethod(),
                'headers'        => $request->getHeaders(false),
                'cookies'        => $request->getCookies(),
                'client_ip'      => $request->getClientIP(),
                'uri'            => (string)$request->getUri(),
                'params_query'   => $request->getUriParams(),
                'params_body'    => $request->getBodyParams(),
                'uploaded_files' => $request->getFiles(true),
            ]));

            return 'ok';
        }
    ],
];
