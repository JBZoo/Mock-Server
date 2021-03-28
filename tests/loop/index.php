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

use Symfony\Component\VarDumper\Dumper\CliDumper;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Task.php';
require_once __DIR__ . '/Scheduler.php';
require_once __DIR__ . '/SystemCall.php';
require_once __DIR__ . '/SysCalls.php';

CliDumper::$defaultColors = true;

/**
 * @param $port
 * @return Generator
 * @throws Exception
 */
function server($port): Generator
{
    echo "Starting server at port $port...\n";

    $socket = @stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) {
        throw new Exception($errStr, $errNo);
    }

    stream_set_blocking($socket, 0);

    while (true) {
        yield waitForRead($socket);
        $clientSocket = stream_socket_accept($socket, 0);
        yield newTask(handleClient($clientSocket));
    }
}

/**
 * @param $socket
 * @return Generator
 */
function handleClient($socket): Generator
{
    yield waitForRead($socket);
    $data = fread($socket, 8192);

    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

    yield waitForWrite($socket);
    fwrite($socket, $response);
    fclose($socket);
}

$scheduler = new Scheduler;
$scheduler->newTask(server(8000));
$scheduler->run();
