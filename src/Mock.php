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

use Amp\Http\Server\Request;
use Amp\Http\Status;
use JBZoo\Data\Data;
use JBZoo\Utils\Cli;
use JBZoo\Utils\Sys;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class Mock
 * @package JBZoo\MockServer
 */
class Mock
{
    /**
     * @var string
     */
    private $sourcePath;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var Request|null
     */
    private $request;

    /**
     * @var int
     */
    private $requestId = 0;

    /**
     * Mock constructor.
     * @param string $mockFilepath
     */
    public function __construct(string $mockFilepath)
    {
        $this->sourcePath = $mockFilepath;
        $this->data = $this->parseSource();
    }

    /**
     * @return Data
     */
    private function parseSource(): Data
    {
        if (file_exists($this->sourcePath)) {
            /** @noinspection PhpIncludeInspection */
            $rawData = (array)include $this->sourcePath;
            return new Data($rawData);
        }

        throw new Exception("File not found: {$this->sourcePath}");
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return sha1($this->sourcePath);
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        $rootPath = dirname(__DIR__);
        return (string)str_replace($rootPath, '.', $this->sourcePath);
    }

    #### Request methods ###############################################################################################

    /**
     * @return array
     */
    public function getRequestMethods(): array
    {
        $methods = $this->data->find('request.method') ?: 'GET';

        if (is_string($methods)) {
            $methods = explode('|', $methods);
        }

        $result = [];
        foreach ($methods as $method) {
            $result[] = strtoupper(trim($method));
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getRequestHeader(): array
    {
        $headers = $this->data->find('request.header') ?: [];
        return (array)$headers;
    }

    /**
     * @return string
     */
    public function getRequestPath(): string
    {
        return (string)$this->data->find('request.path', '/');
    }

    #### Response methods ##############################################################################################

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return (int)$this->data->find('response.code', Status::OK);
    }

    /**
     * @return array
     */
    public function getResponseHeaders(): array
    {
        $headers = (array)$this->data->find('response.headers', ['content-type' => 'text/plain']);

        $headers['X-Mock-Server-Fixture'] = $this->getFilename();
        $headers['X-Mock-Server-Request-Id'] = $this->requestId;

        return $headers;
    }

    /**
     * @return string
     */
    public function getResponseBody(): string
    {
        $bodyHandler = $this->data->find('response.body', '');

        $bodyResponse = $bodyHandler;
        if (is_callable($bodyHandler)) {
            $bodyResponse = $bodyHandler($this->request, $this->requestId);
        }

        return (string)$bodyResponse;
    }

    /**
     * @param Request $request
     * @param int     $requestId
     */
    public function bindRequest(Request $request, int $requestId): void
    {
        $this->request = $request;
        $this->requestId = $requestId;
    }

    /**
     * @param string $sourcePath
     * @return string|null
     */
    public static function isSourceValid(string $sourcePath): ?string
    {
        try {
            Cli::exec(Sys::getBinary() . " -l {$sourcePath}");
        } catch (ProcessFailedException $exception) {
            return (string)$exception->getProcess()->getOutput();
        }

        return null;
    }
}
