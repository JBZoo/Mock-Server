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

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;

use function JBZoo\Data\json;

final class ErrorHandler implements \Amp\Http\Server\ErrorHandler
{
    /**
     * {@inheritdoc}
     */
    public function handleError(int $statusCode, ?string $reason = null, ?Request $request = null): Promise
    {
        $reason = $reason ?: 'Route not found or something went wrong. See server logs.';

        $message = \trim("{$statusCode} {$reason}");
        $body    = $message;

        if ($request) {
            $body = (string)json([
                'fatal_error' => $message,
                'request'     => [
                    'uri'     => (string)$request->getUri(),
                    'headers' => $request->getHeaders(),
                ],
            ]);
        }

        $response = new Response($statusCode, ['Content-Type' => 'application/json'], $body);
        $response->setStatus($statusCode, $reason);

        return new Success($response);
    }
}
