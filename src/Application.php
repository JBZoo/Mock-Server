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

namespace JBZoo\MockServer;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Loop;
use Amp\Socket\Server;
use JBZoo\Utils\Env;
use Monolog\Logger;
use Symfony\Component\Finder\Finder;

/**
 * Class Application
 * @package JBZoo\MockServer
 */
class Application
{
    public const DEFAULT_HOST = '0.0.0.0';
    public const DEFAULT_PORT = '8089';

    /**
     * @var HttpServer
     */
    private $server;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var int
     */
    private $requestId = 0;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->logger = self::initLogger();
        $this->server = new HttpServer(self::getServers(), $this->initRouter(), $this->logger);
    }

    public function start(): void
    {
        $this->logger->debug('PHP version: ' . PHP_VERSION);
        Loop::run(function () {
            yield $this->server->start();

            // @phan-suppress-next-line PhanTypeMismatchArgument
            Loop::onSignal(\SIGINT, function (string $watcherId) {
                Loop::cancel($watcherId);
                yield $this->server->stop();
            });
        });
    }

    /**
     * @return array
     * @throws \Amp\Socket\SocketException
     */
    private static function getServers(): array
    {
        $host = Env::string('JBZOO_MOCK_HOST', self::DEFAULT_HOST);
        $port = Env::string('JBZOO_MOCK_PORT', self::DEFAULT_PORT);

        return [
            Server::listen("{$host}:{$port}")
        ];
    }

    /**
     * @return Router
     */
    private function initRouter(): Router
    {
        $mocks = $this->getMocks();
        if (count($mocks) === 0) {
            $this->logger->error('Mocks not found. Exit.');
        }

        $this->logger->info('Mocks found: ' . count($mocks));

        $router = new Router();
        $router->setFallback(new CallableRequestHandler(function (Request $request): void {
            $this->logger->info("Not found route for \"{$request->getMethod()} {$request->getUri()}\"");
        }));

        foreach ($mocks as $mock) {
            $requestHandler = new CallableRequestHandler(function (Request $request) use ($mock) {
                $this->requestId++;

                $message = "#{$this->requestId} {$mock->getResponseCode()} " .
                    "{$request->getMethod()} {$request->getUri()}";

                $this->logger->info($message);
                $mock->bindRequest($request, $this->requestId);

                return new Response(
                    $mock->getResponseCode(),
                    $mock->getResponseHeaders(),
                    $mock->getResponseBody()
                );
            });

            try {
                $methods = $mock->getRequestMethods();
                foreach ($methods as $method) {
                    $router->addRoute($method, $mock->getRequestPath(), $requestHandler);
                }
            } catch (\Exception $exception) {
                $this->logger->warning($exception->getMessage());
            }
        }

        return $router;
    }

    /**
     * @return Logger
     */
    private static function initLogger(): Logger
    {
        $logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter("[%datetime%] %level_name%: %message%\r\n"));
        $logHandler->setLevel(Logger::DEBUG);

        $logger = new Logger('MockServer');
        $logger->pushHandler($logHandler);

        return $logger;
    }

    /**
     * @return Mock[]
     */
    private function getMocks(): array
    {
        //$mocksPath = realpath($this->getOption('mocks')) ?: dirname(__DIR__) . '/mocks';
        $mocksPath = dirname(__DIR__) . '/tests/mocks';
        $this->logger->info("Mocks Path: {$mocksPath}");

        $finder = (new Finder())
            ->in($mocksPath)
            ->files()
            ->name(".php")
            ->name("*.php")
            ->name(".*.php")
            ->ignoreDotFiles(false)
            ->followLinks();

        $mocks = [];
        foreach ($finder as $file) {
            $filePath = $file->getPathname();

            $validationErrors = Mock::isSourceValid($filePath);
            if (!$validationErrors) {
                $mock = new Mock($filePath);
                $mocks[$mock->getHash()] = $mock;
            } else {
                $this->logger->warning("Fixture \"{$filePath}\" is invalid and skipped\n{$validationErrors}");
            }
        }

        return $mocks;
    }
}
