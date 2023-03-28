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
        'method' => 'POST|GET',
        'path'   => '/' . \pathinfo(__FILE__, \PATHINFO_FILENAME),
    ],

    'response' => [
        'code' => 200,
        'body' => static fn (Request $request): string => (string)json([]),
    ],
];
