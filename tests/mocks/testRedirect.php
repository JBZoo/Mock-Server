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

use function JBZoo\Data\json;

return [
    'request' => [
        'path' => '/' . pathinfo(__FILE__, PATHINFO_FILENAME)
    ],

    'response' => [
        'code'    => 301,
        'headers' => [
            'location' => '/testStructureOfResponse'
        ],
        'body'    => (string)json([
            'message' => 'redirect'
        ])
    ]
];
