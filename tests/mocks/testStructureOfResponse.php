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

use function JBZoo\Data\json;

return [
    'request' => [
        'method' => 'get',
        'path'   => '/' . pathinfo(__FILE__, PATHINFO_FILENAME)
    ],

    'response' => [
        'code'    => 200,
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body'    => (string)json([
            'message' => 'Hello'
        ])
    ]
];
