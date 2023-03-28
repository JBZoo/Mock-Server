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

return [
    'request' => [
        'method' => 'PUT|POST',
        'path'   => '/' . pathinfo(__FILE__, PATHINFO_FILENAME)
    ],

    'response' => [
        'code' => static function (Request $request): int {
            return $request->getMethod() === 'PUT' ? 200 : 404;
        },
    ]
];
