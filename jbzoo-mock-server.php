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

use JBZoo\MockServer\StartCommand;
use Symfony\Component\Console\Application;

umask(0000);
date_default_timezone_set('UTC');

require __DIR__ . '/src/functions.php';
require __DIR__ . '/vendor/autoload.php';

$application = new Application();
$application->add(new StartCommand());
$application->setDefaultCommand('start');
$application->run();
