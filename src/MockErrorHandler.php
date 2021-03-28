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

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;

use function JBZoo\Data\json;

/**
 * Class MockErrorHandler
 * @package JBZoo\MockServer
 */
final class MockErrorHandler implements ErrorHandler
{
    /** {@inheritdoc} */
    public function handleError(int $statusCode, string $reason = null, Request $request = null): Promise
    {
        $message = "Undefined request. {$statusCode} {$reason}";
        $body = $message;
        if ($request) {
            $body = (string)json([
                'fatal_error' => $message,
                'request'     => [
                    'uri'     => (string)$request->getUri(),
                    'headers' => $request->getHeaders(),
                ],
                'trace'       => self::getTrace()
            ]);
        }

        $response = new Response($statusCode, ['Content-Type' => 'application/json'], $body);
        $response->setStatus($statusCode, $reason);

        return new Success($response);
    }

    /**
     * @return string
     */
    private static function getTrace(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $result = [];
        foreach ($trace as $index => $traceRow) {
            $result[] = $index . ': ' . self::getOneTrace($traceRow);
        }

        return implode("\n", $result);
    }

    /**
     * Get formated one trace info
     * @param array $traceRow One trace element
     * @return string
     */
    private static function getOneTrace(array $traceRow): string
    {
        $result = [];

        if (isset($traceRow['file'])) {
            $result['file'] = $traceRow['file'] . ':' . $traceRow['line'];
        }

        $isIncluding = in_array($traceRow['function'], ['include', 'include_once', 'require', 'require_once'], true);

        if ($isIncluding) {
            $includedFile = $traceRow['args'][0] ?? '';
            $result['func'] = "{$traceRow['function']} ('{$includedFile}')";
        } elseif (isset($traceRow['type'], $traceRow['class'])) {
            $result['func'] = "{$traceRow['class']}{$traceRow['type']}{$traceRow['function']}()";
        } else {
            $result['func'] = $traceRow['function'] . '()';
        }

        return $result['file'] ?? $result['func'];
    }
}
