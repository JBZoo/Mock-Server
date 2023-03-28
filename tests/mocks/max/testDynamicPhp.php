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
        'method' => 'GET|POST',
        'path'   => '/' . \pathinfo(__FILE__, \PATHINFO_FILENAME),
    ],

    'response' => [
        'code' => static fn (?Request $request = null): int => 200,

        'headers' => static fn (?Request $request = null): array => ['Content-Type' => 'application/json'],

        'body' => static fn (?Request $request = null): string => (string)json(['result' => 'ok']),
    ],

    'control' => [
        'crazy' => static fn (): bool => false,
        'delay' => static fn (?Request $request = null): int => 1000,
    ],
];
