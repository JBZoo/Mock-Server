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

use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\Rfc7230;
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

    function dumpRequestTrace(Request $request): void
    {
        dump("{$request->getMethod()} {$request->getUri()} HTTP/". \implode('+', $request->getProtocolVersions()));

        /** @noinspection PhpUnhandledExceptionInspection */
        print Rfc7230::formatHeaders($request->getHeaders()) . "\r\n\r\n";
    }

    function dumpResponseTrace(Response $response): void
    {
        dump("HTTP/{$response->getProtocolVersion()} {$response->getStatus()} {$response->getReason()}");

        /** @noinspection PhpUnhandledExceptionInspection */
        print Rfc7230::formatHeaders($response->getHeaders()) . "\r\n\r\n";
    }

    function dumpResponseBodyPreview(string $body): void
    {
        $bodyLength = \strlen($body);

        if ($bodyLength < 250) {
            print $body . "\r\n";
        } else {
            print \substr($body, 0, 250) . "\r\n\r\n";
            print($bodyLength - 250) . " more bytes\r\n";
        }
    }
}