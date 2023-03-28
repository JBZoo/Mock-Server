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

return [
    'description' => 'Some text that describes the intended usage of the current configuration',

    'request' => [
        'host'                  => 'example.com',
        'queryStringParameters' => ['name' => ['value', 'value']],
        'headers'               => ['name' => ['value']],
        'cookies'               => ['name' => 'value'],
    ],

    'response' => [
        'statusCode' => 'int (2xx,4xx,5xx,xxx)',
        'headers'    => ['name' => ['value']],
        'cookies'    => ['name' => 'value'],
        'body'       => 'Response body',
    ],

    'callback' => [
        'method'  => 'GET|POST|PUT|PATCH|...',
        'url'     => 'http://your-callback/',
        'delay'   => 'string (response delay in s,ms)',
        'headers' => [
            'name' => ['value'],
        ],
        'body' => 'Response body',
    ],

    'control' => [
        'scenario' => [
            'name' => 'string (scenario name)',

            'requiredState' => [
                'not_started (default state)',
                'another_state_name',
            ],

            'newState' => 'new_stat_neme',
        ],

        'proxyBaseURL' => 'string (original URL endpoint)',
        'priority'     => 'int (matching priority)',
        'webHookURL'   => 'string (URL endpoint)',
    ],
];
