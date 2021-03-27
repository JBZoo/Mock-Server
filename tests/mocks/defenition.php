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

return [
    'description' => 'Some text that describes the intended usage of the current configuration',

    'request' => [
        'host'   => 'example.com',
        'method' => 'GET|POST|PUT|PATCH|...',
        'path'   => '/your/path/:variable',

        'queryStringParameters' => [
            'name' => ['value', 'value'],
        ],

        'headers' => ['name' => ['value',],],
        'cookies' => ['name' => 'value',],

        'body' => 'Expected Body',
    ],

    'response' => [
        'statusCode' => 'int (2xx,4xx,5xx,xxx)',
        'headers'    => [
            'name' => ['value'],
        ],
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
        'body'    => 'Response body',
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
        'delay'        => 'string (response delay in s,ms)',
        'crazy'        => 'bool (return random 5xx)',
        'priority'     => 'int (matching priority)',
        'webHookURL'   => 'string (URL endpoint)',
    ],
];
