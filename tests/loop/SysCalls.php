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

function getTaskId()
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) {
        $task->setSendValue($task->getTaskId());
        $scheduler->schedule($task);
    });
}

function newTask(Generator $coroutine)
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) use ($coroutine) {
        $task->setSendValue($scheduler->newTask($coroutine));
        $scheduler->schedule($task);
    });
}

function killTask($tid)
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) use ($tid) {
        $task->setSendValue($scheduler->killTask($tid));
        $scheduler->schedule($task);
    });
}

function waitForRead($socket)
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) use ($socket) {
        $scheduler->waitForRead($socket, $task);
    });
}

function waitForWrite($socket)
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) use ($socket) {
        $scheduler->waitForWrite($socket, $task);
    });
}
