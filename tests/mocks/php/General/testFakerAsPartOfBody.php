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

use function JBZoo\Data\json;

require_once __DIR__ . '/../../../../vendor/autoload.php';

return [
    'request' => [
        'path' => '/' . \pathinfo(__FILE__, \PATHINFO_FILENAME),
    ],

    'response' => [
        'body' => static fn (): string => (string)json(['name' => Faker\Factory::create()->name]),
    ],
];
