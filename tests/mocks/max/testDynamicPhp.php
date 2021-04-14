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
        'method' => "GET|POST",
        'path'   => '/' . pathinfo(__FILE__, PATHINFO_FILENAME)
    ],

    'response' => [
        'code' => static function (?Request $request = null): int {
            return 200;
        },

        'headers' => static function (?Request $request = null): array {
            return ['Content-Type' => 'application/json'];
        },

        'body' => static function (?Request $request = null): string {
            return (string)json(['result' => 'ok']);
        },
    ],

    'control' => [
        'crazy' => static function (): bool {
            return false;
        },
        'delay' => static function (?Request $request = null): int {
            return 1000;
        },
    ]
];
