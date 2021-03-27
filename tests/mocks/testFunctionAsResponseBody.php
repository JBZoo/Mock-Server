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

use Amp\Http\Server\Request;

use function JBZoo\Data\json;

return [
    'request' => [
        'method' => 'POST',
        'path'   => '/' . pathinfo(__FILE__, PATHINFO_FILENAME)
    ],

    'response' => [
        'body' => static function (Request $request, $requestId): string {
            $headers = $request->getHeaders();
            unset($headers['content-length']);

            return (string)json([
                'request_id' => $requestId,
                'request'    => [
                    'uri'      => (string)$request->getUri(),
                    'method'   => $request->getMethod(),
                    'protocol' => $request->getProtocolVersion(),
                    'headers'  => $headers,
                ],
            ]);
        }
    ]
];
