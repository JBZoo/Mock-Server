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

namespace FastRoute;

use FastRoute\RouteParser\Std;

if (!function_exists('FastRoute\simpleDispatcher')) {
    /**
     * @param callable $routeDefinitionCallback
     * @param array    $options
     * @return Dispatcher
     * @phan-suppress PhanRedefineFunction
     */
    function simpleDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
    {
        $options += [
            'routeParser'    => Std::class,
            'dataGenerator'  => DataGenerator\GroupCountBased::class,
            'dispatcher'     => Dispatcher\GroupCountBased::class,
            'routeCollector' => RouteCollector::class,
        ];

        $routeParser = new $options['routeParser']();
        $dataGenerator = new $options['dataGenerator']();

        /** @var RouteCollector $routeCollector */
        $routeCollector = new $options['routeCollector']($routeParser, $dataGenerator);

        $routeDefinitionCallback($routeCollector);

        return new $options['dispatcher']($routeCollector->getData());
    }
}
