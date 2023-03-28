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

namespace JBZoo\MockServer\Server;

use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\Request as ServerRequest;
use JBZoo\Utils\Url;
use Psr\Http\Message\UriInterface;

class Request
{
    /** @var int */
    private $requestId;

    /** @var ServerRequest */
    private $request;

    /** @var Form */
    private $form;

    public function __construct(int $requestId, ServerRequest $request, Form $form)
    {
        $this->requestId = $requestId;
        $this->request   = $request;
        $this->form      = $form;
    }

    public function getId(): int
    {
        return $this->requestId;
    }

    public function getHeaders(bool $allKeys = true): array
    {
        $excludedKeys = [
            'Host',
            'Content-Type',
            'Content-Length',
        ];

        $headerKeys = \array_keys($this->request->getHeaders());
        $result     = [];

        foreach ($headerKeys as $headerKey) {
            $result[$headerKey] = $this->request->getHeader((string)$headerKey);
        }

        if (!$allKeys) {
            foreach ($excludedKeys as $excludedKey) {
                unset($result[$excludedKey], $result[\strtolower($excludedKey)]);
            }
        }

        return $result;
    }

    public function getHeader(string $header): ?string
    {
        return $this->request->getHeader($header);
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    public function getCookies(): array
    {
        $cookies = $this->request->getCookies();

        $result = [];

        foreach ($cookies as $cookie) {
            $result[$cookie->getName()] = $cookie->getValue();
        }

        return $result;
    }

    public function getFiles(bool $contentAsBase64 = false): array
    {
        $files = $this->form->getFiles();

        $result = [];

        foreach ($files as $fileName => $filesByName) {
            foreach ($filesByName as $fileByName) {
                $result[$fileName] ??= [];

                $result[$fileName][] = [
                    'name'     => $fileByName->getName(),
                    'mime'     => $fileByName->getMimeType(),
                    'contents' => $contentAsBase64
                        ? \base64_encode($fileByName->getContents())
                        : $fileByName->getContents(),
                ];
            }
        }

        return $result;
    }

    public function getUriParams(): array
    {
        \parse_str($this->getUri()->getQuery(), $result);

        return $result;
    }

    public function getUserAgent(): ?string
    {
        return $this->getHeader('user-agent');
    }

    public function getClientIP(): ?string
    {
        return $this->request->getClient()->getRemoteAddress()->getHost();
    }

    public function getBodyParams(): array
    {
        $names = $this->form->getNames();

        $values = [];

        foreach ($names as $name) {
            if ($name !== '') {
                $values[$name] = $this->form->getValue($name);
            }
        }

        \parse_str(Url::build($values), $result);

        return $result;
    }

    public function getAllParams(): array
    {
        return \array_merge($this->getUriParams(), $this->getBodyParams(), $this->getCookies());
    }
}
