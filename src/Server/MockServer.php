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

namespace JBZoo\MockServer\Server;

use Amp\Delayed;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Options;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Loop;
use Amp\Socket\BindContext;
use Amp\Socket\Certificate;
use Amp\Socket\Server as SocketServer;
use Amp\Socket\ServerTlsContext;
use JBZoo\MockServer\Mocks\AbstractMock;
use JBZoo\MockServer\Mocks\PhpMock;
use JBZoo\Utils\FS;
use JBZoo\Utils\Timer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use function Amp\call;
use function Amp\Http\Server\FormParser\parseForm;

/**
 * Class MockServer
 * @package JBZoo\MockServer\Server
 */
class MockServer
{
    public const DEFAULT_HOST      = '0.0.0.0';
    public const DEFAULT_HOST_IPV6 = '[::]';

    public const DEFAULT_PORT     = 8089;
    public const DEFAULT_PORT_TLS = 8090;

    // Looks like, it's good for local testing, experiments
    public const LIMIT_BODY_SIZE          = 20 * 1024 * 1024;
    public const LIMIT_HEADER_SIZE        = 64 * 1024;
    public const LIMIT_TIMEOUT            = 60;
    public const LIMIT_TIMEOUT_TLS_SETUP  = 30;
    public const LIMIT_TIMEOUT_TRANSFER   = 0;
    public const LIMIT_CONNECTIONS_PER_IP = 200;
    public const LIMIT_CONNECTIONS        = 20000;
    public const LIMIT_CONCURRENT_STREAM  = 1024;

    public const PROXY_DEBUG_MODE = false;

    /**
     * @var HttpServer
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
     * @var string
     */
    private $hostTls = self::DEFAULT_HOST;

    /**
     * @var int
     */
    private $portTls = self::DEFAULT_PORT_TLS;

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
        $this->logger = $this->initLogger();

        // Almost unlimited
        $serverOption = (new Options())
            ->withBodySizeLimit(self::LIMIT_BODY_SIZE)
            ->withHeaderSizeLimit(self::LIMIT_HEADER_SIZE)
            ->withHttp1Timeout(self::LIMIT_TIMEOUT)
            ->withHttp2Timeout(self::LIMIT_TIMEOUT)
            ->withTlsSetupTimeout(self::LIMIT_TIMEOUT_TLS_SETUP)
            ->withConnectionsPerIpLimit(self::LIMIT_CONNECTIONS_PER_IP)
            ->withConnectionLimit(self::LIMIT_CONNECTIONS)
            ->withConcurrentStreamLimit(self::LIMIT_CONCURRENT_STREAM);

        $this->server = new HttpServer($this->getServers(), $this->initRouter(), $this->logger, $serverOption);
        $this->server->setErrorHandler(new ErrorHandler());

        Loop::run(function () {
            yield $this->server->start();

            $this->showDebugInfo();
            $this->logger->info('Ready to work.');

            //Loop::repeat($msInterval = 10000, function () { $this->showDebugInfo(true);});

            // @phan-suppress-next-line PhanTypeMismatchArgument
            Loop::onSignal(\SIGINT, function (string $watcherId) {
                Loop::cancel($watcherId);
                yield $this->server->stop();
            });
        });
    }

    /**
     * @return array
     */
    private function getServers(): array
    {
        $cert = new Certificate(__DIR__ . '/../../vendor/amphp/http-server/tools/tls/localhost.pem');
        $context = (new BindContext())->withTlsContext((new ServerTlsContext())->withDefaultCertificate($cert));

        return [
            SocketServer::listen("{$this->host}:{$this->port}"),
            SocketServer::listen("{$this->hostTls}:{$this->portTls}", $context),
        ];
    }

    /**
     * @return Router
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function initRouter(): Router
    {
        $mocks = $this->getMocks();
        if (count($mocks) === 0) {
            $this->logger->error('Mocks not found. Exit.');
            die(1);
        }

        $this->logger->info('Mocks found: <comment>' . count($mocks) . '</comment>');

        $router = new Router();
        $router->setFallback(new CallableRequestHandler(function (ServerRequest $request): void {
            $this->logger->error(
                "Route not found: <important>{$request->getMethod()} {$request->getUri()}</important>"
            );
        }));

        $totalRoutes = 0;
        foreach ($mocks as $mock) {
            $requestHandler = new CallableRequestHandler(function (ServerRequest $request) use ($mock) {
                $mock->bindRequest(null);

                $customDelay = $mock->getDelay();
                if ($customDelay > 0) {
                    yield new Delayed($customDelay);
                }

                $requestId = ++$this->requestId;

                /** @var Request $jbRequest */
                $jbRequest = yield call(static function () use ($requestId, $request) {
                    return new Request($requestId, $request, yield parseForm($request));
                });

                $mock->bindRequest($jbRequest);

                if ($proxyUrl = $mock->getBaseProxyUrl()) {
                    [$responseCode, $responseHeaders, $responseBody] =
                        yield call(static function () use ($mock, $proxyUrl) {
                            return $mock->handleProxyUrl($proxyUrl);
                        });
                    $this->logger->notice("#{$requestId} <warning>Proxy</warning> {$proxyUrl}");
                } else {
                    $responseCode = $mock->getResponseCode();
                    $responseHeaders = $mock->getResponseHeaders();
                    $responseBody = $mock->getResponseBody();
                }

                $this->logger->notice(implode(" ", array_filter([
                    "#{$requestId}",
                    $responseCode,
                    "- {$request->getMethod()} {$request->getUri()}",
                    $mock->isCrazyEnabled() ? "<important>Crazy</important>" : '',
                    $customDelay > 0 ? "<warning>Delay: {$customDelay}ms</warning>" : ''
                ])));

                return new Response($responseCode, $responseHeaders, $responseBody);
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

        $this->logger->info("Routes added: <comment>{$totalRoutes}</comment>");

        return $router;
    }

    /**
     * @return LoggerInterface
     * @phan-suppress PhanUndeclaredMethod
     */
    private function initLogger(): LoggerInterface
    {
        /** @phpstan-ignore-next-line */
        foreach ([$this->output, $this->output->getErrorOutput()] as $output) { //
            $formatter = $output->getFormatter();
            $formatter->setStyle('debug', new OutputFormatterStyle('cyan'));
            $formatter->setStyle('warning', new OutputFormatterStyle('yellow'));
            $formatter->setStyle('important', new OutputFormatterStyle('red'));
            $formatter->setStyle('filename', new OutputFormatterStyle('cyan'));
        }

        return new ConsoleLogger($this->output);
    }

    /**
     * @return AbstractMock[]
     */
    private function getMocks(): array
    {
        $relPath = FS::getRelative($this->mocksPath);
        $this->logger->info(str_replace($relPath, "<comment>{$relPath}</comment>", "Mocks Path: {$this->mocksPath}"));

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
                $validationErrors = AbstractMock::isSourceValid($filePath);
            }

            if (!$validationErrors) {
                $mock = new PhpMock($filePath);
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
     * @param string $hostTls
     * @return $this
     */
    public function setHostTls(string $hostTls): self
    {
        $this->hostTls = $hostTls;
        return $this;
    }

    /**
     * @param int $portTls
     * @return $this
     */
    public function setPortTls(int $portTls): self
    {
        $this->portTls = $portTls;
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

    /**
     * @param bool $showOnlyMemory
     */
    private function showDebugInfo(bool $showOnlyMemory = false): void
    {
        if ($showOnlyMemory) {
            $this->logger->debug("Memory Usage: {$this->getMemoryUsage()}");
        } else {
            $this->logger->debug('PHP Version: ' . PHP_VERSION);
            $this->logger->debug('Driver: ' . get_class(Loop::get()));
            $this->logger->debug("Memory Usage: {$this->getMemoryUsage()}");
            $this->logger->debug('Bootstrap time: ' . round(microtime(true) - Timer::getRequestTime(), 3) . ' sec');
        }
    }

    /**
     * @return string
     * @phan-suppress PhanPluginPossiblyStaticPrivateMethod
     */
    private function getMemoryUsage(): string
    {
        return FS::format(memory_get_usage(false)) . '/' . FS::format(memory_get_peak_usage(false));
    }
}
