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

use JBZoo\MockServer\Server\Request;

use function JBZoo\Data\json;

return [
    'request' => [
        'method' => '*',
        'path'   => '/' . \pathinfo(__FILE__, \PATHINFO_FILENAME),
    ],

    'response' => [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => static function (Request $request): string {
            $uri = $request->getUri();

            return (string)json([
                'id' => $request->getId(),

                'protocol'   => $request->getProtocolVersion(),
                'method'     => $request->getMethod(),
                'headers'    => $request->getHeaders(),
                'cookies'    => $request->getCookies(),
                'user_agent' => $request->getUserAgent(),
                'client_ip'  => $request->getClientIP(),

                'uri' => [
                    'full'      => (string)$uri,
                    'scheme'    => $uri->getScheme(),
                    'host'      => $uri->getHost(),
                    'port'      => $uri->getPort(),
                    'authority' => $uri->getAuthority(),
                    'path'      => $uri->getPath(),
                    'query'     => $uri->getQuery(),
                    'user_info' => $uri->getUserInfo(),
                ],

                'params' => [
                    'query' => $request->getUriParams(),  // It's like $_GET in PHP
                    'body'  => $request->getBodyParams(), // It's like $_POST in PHP
                    'all'   => $request->getAllParams(),  // It's like $_REQUEST = $_GET + $_POST + $_COOKIE (merging)
                ],

                'uploaded_files' => $request->getFiles(true),
            ]);
        },
    ],
];
