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

namespace JBZoo\MockServer;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Delayed;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\Server;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Loop;
use Amp\Socket\Server as SocketServer;
use JBZoo\Utils\FS;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use function Amp\Http\Server\FormParser\parseForm;

/**
 * Class MockServer
 * @package JBZoo\MockServer
 */
class MockServer
{
    public const DEFAULT_HOST = '0.0.0.0';
    public const DEFAULT_PORT = 8089;

    //private const LOG_FORMAT = "[%datetime%] %level_name%: %message% %context% %extra%\r\n";
    private const LOG_FORMAT = "%level_name%: %message% %context% %extra%\r\n";

    /**
     * @var Server
     */
    private $server;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $requestId = 0;

    /**
     * @var string
     */
    private $host = self::DEFAULT_HOST;

    /**
     * @var int
     */
    private $port = self::DEFAULT_PORT;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $mocksPath;

    /**
     * @var bool
     */
    private $checkSyntax = false;

    public function start(): void
    {
        $this->logger = self::initLogger();

        $this->server = new Server($this->getServers(), $this->initRouter(), $this->logger);
        $this->server->setErrorHandler(new ErrorHandler());

        Loop::run(function () {
            yield $this->server->start();

            $this->logger->debug('PHP Version: ' . PHP_VERSION);
            $this->logger->debug('Peak Usage Memory: ' . FS::format(memory_get_peak_usage(false)));
            $bootstrapTime = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3);
            $this->logger->debug("Time to start: {$bootstrapTime} sec");
            $this->logger->info('Ready to work.');

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
    private function getServers(): array
    {
        return [SocketServer::listen("{$this->host}:{$this->port}")];
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
        $router->setFallback(new CallableRequestHandler(function (ServerRequest $request): void {
            $this->logger->error("Route not found: \"{$request->getMethod()} {$request->getUri()}\"");
        }));

        $totalRoutes = 0;
        foreach ($mocks as $mock) {
            $requestHandler = new CallableRequestHandler(function (ServerRequest $request) use ($mock) {
                $msDelay = $mock->getDelay();
                if ($msDelay > 0) {
                    yield new Delayed($msDelay);
                }

                $this->requestId++;
                $form = yield parseForm($request);
                $mock->bindRequest(new Request($this->requestId, $request, $form));

                $this->logger->debug(implode("\t", [
                    "#{$this->requestId}",
                    $mock->getResponseCode(),
                    $request->getMethod(),
                    $request->getUri()
                ]));

                return new Response($mock->getResponseCode(), $mock->getResponseHeaders(), $mock->getResponseBody());
            });

            try {
                $methods = $mock->getRequestMethods();
                foreach ($methods as $method) {
                    $router->addRoute($method, $mock->getRequestPath(), $requestHandler);
                    $totalRoutes++;
                }
            } catch (\Exception $exception) {
                $this->logger->warning($exception->getMessage());
            }
        }

        $this->logger->info("Routes added: {$totalRoutes}");

        return $router;
    }

    /**
     * @return LoggerInterface
     */
    private static function initLogger(): LoggerInterface
    {
        $streamHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
        $consoleFormatter = new ConsoleFormatter(self::LOG_FORMAT);
        $consoleFormatter->ignoreEmptyContextAndExtra(true);

        $streamHandler->setFormatter($consoleFormatter);
        $streamHandler->setLevel(Logger::DEBUG);

        $logger = new Logger('MockServer');
        $logger->pushHandler($streamHandler);

        return $logger;
        //return new ConsoleLogger($this->output);
    }

    /**
     * @return Mock[]
     */
    private function getMocks(): array
    {
        $this->logger->info("Mocks Path: {$this->mocksPath}");

        $finder = (new Finder())
            ->in($this->mocksPath)
            ->files()
            ->name(".php")
            ->name("*.php")
            ->name(".*.php")
            ->ignoreDotFiles(false)
            ->followLinks();

        $mocks = [];
        foreach ($finder as $file) {
            $filePath = $file->getPathname();

            $validationErrors = null;
            if ($this->checkSyntax) {
                $validationErrors = Mock::isSourceValid($filePath);
            }

            if (!$validationErrors) {
                $mock = new Mock($filePath);
                $mocks[$mock->getHash()] = $mock;
            } else {
                $this->logger->warning("Fixture \"{$filePath}\" is invalid and skipped\n{$validationErrors}");
            }
        }

        return $mocks;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @param bool $checkSyntax
     * @return $this
     */
    public function setCheckSyntax(bool $checkSyntax): self
    {
        $this->checkSyntax = $checkSyntax;
        return $this;
    }

    /**
     * @param string $mocksPath
     * @return $this
     */
    public function setMocksPath(string $mocksPath): self
    {
        $path = realpath($mocksPath);
        if (!$path) {
            throw new Exception("Mock path not found: {$mocksPath}");
        }

        $this->mocksPath = $path;
        return $this;
    }
}
