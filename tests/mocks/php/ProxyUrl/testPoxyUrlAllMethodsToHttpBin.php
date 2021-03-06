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

use function JBZoo\Data\json;

return [
    'request' => [
        'method' => "*",
        'path'   => '/' . pathinfo(__FILE__, PATHINFO_FILENAME)
    ],

    'response' => [ // ignored if "control.proxyBaseUrl" is presented
        'code'    => 404,
        'headers' => static function (): array {
            return ['x-random-value' => random_int(0, 10000000)];
        },
        'body'    => (string)json(['test' => 'failed'])
    ],

    'control' => [
        'proxyBaseUrl' => static function (Request $request) {
            return 'http://httpbin.org/' . strtolower($request->getMethod());
        }
    ]
];
