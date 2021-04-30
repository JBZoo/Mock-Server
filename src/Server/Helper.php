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

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JBZoo\HttpClient\HttpClient;

/**
 * Class Helper
 * @package JBZoo\MockServer\Server
 */
class Helper
{
    /**
     * @param string $url
     * @param string $method
     * @param array  $headers
     * @param array  $uriParams
     * @param array  $formParams
     * @param array  $uploadFiles
     * @return array
     * @throws GuzzleException
     */
    public static function syncHttpRequest(
        string $url,
        string $method = 'GET',
        array $headers = [],
        array $uriParams = [],
        array $formParams = [],
        array $uploadFiles = []
    ): array {

        $response = (new HttpClient())->request($url, $method);
        return [
            $response->getCode(),
            $response->getHeaders(),
            $response->getBody()
        ];

        $guzzleMultipart = [];
        foreach ($uploadFiles as $varName => $files) {
            foreach ($files as $file) {
                $guzzleMultipart[] = ['name' => $varName, 'contents' => $file['contents'], 'filename' => $file['name']];
            }
        }

        $headers['user-agent'] = $headers['user-agent'] ?? 'Mock-Server Webhook';

        $options = [
            RequestOptions::HEADERS         => $headers,

            //RequestOptions::TIMEOUT         => MockServer::LIMIT_TIMEOUT,
            //RequestOptions::CONNECT_TIMEOUT => MockServer::LIMIT_TIMEOUT,
            //RequestOptions::READ_TIMEOUT    => MockServer::LIMIT_TIMEOUT,
            RequestOptions::TIMEOUT         => 1,
            RequestOptions::CONNECT_TIMEOUT => 1,
            RequestOptions::READ_TIMEOUT    => 1,

            //RequestOptions::DEBUG => MockServer::PROXY_DEBUG_MODE,
            RequestOptions::DEBUG => true,

            RequestOptions::HTTP_ERRORS => false,
        ];

        if ('GET' === $method && count($uriParams) > 0) {
            $options[RequestOptions::QUERY] = $uriParams;
        } elseif (count($guzzleMultipart) > 0) {
            $options[RequestOptions::MULTIPART] = $guzzleMultipart;
        } elseif (count($formParams) > 0) {
            $options[RequestOptions::FORM_PARAMS] = $formParams;
        }

        try {
            $response = (new GuzzleHttpClient())->request($method, $url, $options);
            return [
                $response->getStatusCode(),
                $response->getHeaders(),
                $response->getBody()->getContents()
            ];
        } catch (\Exception $exception) {
            return [0, [], $exception->getMessage()];
        }
    }
}
