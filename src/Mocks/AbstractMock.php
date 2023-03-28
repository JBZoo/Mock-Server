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

namespace JBZoo\MockServer\Mocks;

use Amp\Http\Status;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\RequestOptions;
use JBZoo\Data\Data;
use JBZoo\MockServer\Server\MockServer;
use JBZoo\MockServer\Server\Request;
use JBZoo\Utils\Cli;
use JBZoo\Utils\Sys;
use Symfony\Component\Process\Exception\ProcessFailedException;

abstract class AbstractMock
{
    protected const FORMAT_CLASS = Data::class;
    // private const CRAZY_MAX_DELAY = 10000; // 10 seconds

    private const CRAZY_POSSIBLE_BODIES = [
        '',
        'Crazy mode is enabled. Received unexpected response ;)',
        '{"result": false}',
        '{"error": "Crazy mode is enabled. Received unexpected response ;)"}',
    ];

    private const CRAZY_POSSIBLE_CODES = [
        // 2xx
        Status::OK,
        // 3xx
        Status::NOT_ACCEPTABLE,
        Status::FORBIDDEN,
        Status::NOT_FOUND,
        // 5xx
        Status::INTERNAL_SERVER_ERROR,
        Status::NOT_IMPLEMENTED,
        Status::BAD_GATEWAY,
        Status::SERVICE_UNAVAILABLE,
        Status::GATEWAY_TIMEOUT,
        Status::HTTP_VERSION_NOT_SUPPORTED,
        Status::VARIANT_ALSO_NEGOTIATES,
        Status::INSUFFICIENT_STORAGE,
        Status::LOOP_DETECTED,
        Status::NOT_EXTENDED,
        Status::NETWORK_AUTHENTICATION_REQUIRED,
    ];

    /** @var string */
    private $sourcePath;

    /** @var Data */
    private $data;

    /** @var null|Request */
    private $request;

    public function __construct(string $mockFilepath)
    {
        $this->sourcePath = $mockFilepath;
        $this->data       = $this->parseSource();
    }

    public function getHash(): string
    {
        return \sha1($this->sourcePath);
    }

    public function getFilename(): string
    {
        $rootPath = \dirname(__DIR__);

        return (string)\str_replace($rootPath, '.', $this->sourcePath);
    }

    // ### Request methods #############################################################################################

    public function getRequestMethods(): array
    {
        $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'HEAD', 'OPTIONS', 'DELETE'];
        $methods      = $this->data->find('request.method') ?: 'GET';

        if (\is_string($methods)) {
            $methods = \explode('|', $methods);
        }

        $result = [];

        foreach ($methods as $method) {
            $addMethods = \strtoupper(\trim($method));
            if ($addMethods === 'ANY' || $addMethods === '*') {
                $addMethods = $validMethods;
            }

            /** @noinspection SlowArrayOperationsInLoopInspection */
            $result = \array_merge($result, (array)$addMethods);
        }

        return \array_filter(\array_unique($result));
    }

    public function getRequestHeader(): array
    {
        $headers = $this->data->find('request.header') ?: [];

        return (array)$headers;
    }

    public function getRequestPath(): string
    {
        return (string)$this->data->find('request.path', '/');
    }

    // ### Response methods ############################################################################################

    public function getResponseCode(): int
    {
        $codeHandler = $this->data->find('response.code', Status::OK);
        $code        = $this->handleCallable($codeHandler, 'int');

        $result = (int)$code;
        if ($this->isCrazyMode()) {
            $result = (int)\array_rand(\array_flip(self::CRAZY_POSSIBLE_CODES));
        }

        return $result;
    }

    public function getResponseHeaders(): array
    {
        $headerHandler = $this->data->find('response.headers', ['content-type' => 'text/plain']);
        $headers       = (array)$this->handleCallable($headerHandler, 'array');

        $debugHeaders = [
            'X-Mock-Server-Fixture'    => $this->getFilename(),
            'X-Mock-Server-Request-Id' => $this->request !== null ? $this->request->getId() : null,
        ];

        if ($this->isCrazyMode()) {
            return $debugHeaders;
        }

        return \array_merge($debugHeaders, $headers);
    }

    public function getResponseBody(): string
    {
        if ($this->isCrazyMode()) {
            return (string)\array_rand(\array_flip(self::CRAZY_POSSIBLE_BODIES));
        }

        $bodyHandler = $this->data->find('response.body', '');
        $body        = $this->handleCallable($bodyHandler, 'string');

        return (string)$body;
    }

    public function bindRequest(?Request $request = null): void
    {
        $this->request = $request;
    }

    public function getDelay(): int
    {
        $delayHandler = $this->data->find('control.delay', 0);
        $delay        = $this->handleCallable($delayHandler, 'int');

        // if ($this->isCrazyEnabled()) {
        //    $delay += random_int(0, self::CRAZY_MAX_DELAY);
        // }

        return (int)$delay;
    }

    public function isCrazyMode(): bool
    {
        $result = false;
        if ($this->isCrazyEnabled()) {
            $result = \random_int(0, 1) === 1; // 50%
        }

        return $result;
    }

    public function isCrazyEnabled(): bool
    {
        $crazyHandler = $this->data->find('control.crazy', false);
        $crazy        = $this->handleCallable($crazyHandler, 'bool');

        return (bool)$crazy;
    }

    public function getBaseProxyUrl(): ?string
    {
        $proxyUrlHandler = $this->data->find('control.proxyBaseUrl');
        $proxyUrl        = $this->handleCallable($proxyUrlHandler, 'string');

        return $proxyUrl ?: null;
    }

    public function handleProxyUrl(string $proxyUrl): array
    {
        if ($this->request === null) {
            throw new Exception('Request object is not set.');
        }

        $allFiles        = $this->request->getFiles(false);
        $guzzleMultipart = [];

        foreach ($allFiles as $varName => $files) {
            foreach ($files as $file) {
                $guzzleMultipart[] = [
                    'name'     => $varName,
                    'contents' => $file['contents'],
                    'filename' => $file['name'],
                ];
            }
        }

        $options = [
            RequestOptions::HEADERS         => $this->request->getHeaders(false),
            RequestOptions::TIMEOUT         => MockServer::LIMIT_TIMEOUT,
            RequestOptions::CONNECT_TIMEOUT => MockServer::LIMIT_TIMEOUT,
            RequestOptions::READ_TIMEOUT    => MockServer::LIMIT_TIMEOUT,
            RequestOptions::DEBUG           => MockServer::PROXY_DEBUG_MODE,
            RequestOptions::HTTP_ERRORS     => false,
        ];

        $isGet = $this->request->getMethod() === 'GET';
        if ($isGet) {
            $options[RequestOptions::QUERY] = $this->request->getUriParams();
        } elseif (\count($guzzleMultipart) > 0) {
            $options[RequestOptions::MULTIPART] = $guzzleMultipart;
        } else {
            $options[RequestOptions::FORM_PARAMS] = $this->request->getBodyParams();
        }

        $response = (new GuzzleHttpClient())->request($this->request->getMethod(), $proxyUrl, $options);

        return [
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()->getContents(),
        ];
    }

    public static function isSourceValid(string $sourcePath): ?string
    {
        try {
            Cli::exec(Sys::getBinary() . " -l {$sourcePath}");
        } catch (ProcessFailedException $exception) {
            return (string)$exception->getProcess()->getOutput();
        }

        return null;
    }

    private function parseSource(): Data
    {
        if (\file_exists($this->sourcePath)) {
            /** @var Data $formatClassName */
            $formatClassName = static::FORMAT_CLASS;

            return new $formatClassName($this->sourcePath);
        }

        throw new Exception("File not found: {$this->sourcePath}");
    }

    /**
     * @param  mixed $handler
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function handleCallable($handler, ?string $expectedResultType = null)
    {
        $result = $handler;

        if (\is_callable($handler)) {
            $result = $handler($this->request ?? null);
        }

        if ($result === null) {
            return null;
        }

        if ($expectedResultType !== null) {
            if ($expectedResultType === 'bool' && !\is_bool($result)) {
                throw new Exception('Expected result of callback is boolean. ' . \gettype($result) . ' given');
            }

            if ($expectedResultType === 'int' && !\is_int($result)) {
                throw new Exception('Expected result of callback is integer. ' . \gettype($result) . ' given');
            }

            if ($expectedResultType === 'string' && !\is_string($result)) {
                throw new Exception('Expected result of callback is string. ' . \gettype($result) . ' given');
            }

            if ($expectedResultType === 'array' && !\is_array($result)) {
                throw new Exception('Expected result of callback is array. ' . \gettype($result) . ' given');
            }
        }

        return $result;
    }
}
