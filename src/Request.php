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

use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\Request as ServerRequest;
use Psr\Http\Message\UriInterface;

class Request
{
    /**
     * @var int
     */
    private $requestId;

    /**
     * @var ServerRequest
     */
    private $request;

    /**
     * @var Form
     */
    private $form;

    /**
     * @var Mock
     */
    private $mock;

    /**
     * Request constructor.
     * @param int           $requestId
     * @param ServerRequest $request
     * @param Form          $form
     * @param Mock          $mock
     */
    public function __construct(int $requestId, ServerRequest $request, Form $form, Mock $mock)
    {
        $this->requestId = $requestId;
        $this->request = $request;
        $this->form = $form;
        $this->mock = $mock;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->requestId;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * @param string $header
     * @return string|null
     */
    public function getHeader(string $header): ?string
    {
        return $this->request->getHeader($header);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    /**
     * @return mixed[]
     */
    public function getAttributes(): array
    {
        return $this->request->getAttributes();
    }

    /**
     * @param string $attribute
     * @return mixed
     */
    public function getAttribute(string $attribute)
    {
        return $this->request->getAttribute($attribute);
    }
}