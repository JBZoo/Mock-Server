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

$default = include __DIR__ . '/../vendor/jbzoo/codestyle/src/phan/default.php';

return array_merge($default, [
    'directory_list' => [
        'src',

        'vendor/jbzoo/utils/src',
        'vendor/jbzoo/data/src',
        'vendor/amphp',
        'vendor/psr',
        'vendor/monolog/monolog/src',
        'vendor/symfony/finder',
        'vendor/symfony/process',
        'vendor/nikic/fast-route/src',
    ]
]);
