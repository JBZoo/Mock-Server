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

/**
 * Class Scheduler
 */
class Scheduler
{
    /**
     * @var int
     */
    protected $maxTaskId = 0;

    /**
     * @var Task[]
     */
    protected $taskMap = [];

    /**
     * @var SplQueue
     */
    protected $taskQueue;

    /**
     * @var array
     */
    protected $waitingForRead = [];

    /**
     * @var array
     */
    protected $waitingForWrite = [];

    /**
     * Scheduler constructor.
     */
    public function __construct()
    {
        $this->taskQueue = new SplQueue();
    }

    /**
     * @param Generator $coroutine
     * @return int
     */
    public function newTask(Generator $coroutine)
    {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);

        return $tid;
    }

    /**
     * @param Task $task
     */
    public function schedule(Task $task)
    {
        $this->taskQueue->enqueue($task);
    }

    public function run(): void
    {
        $this->newTask($this->ioPollTask());

        while (!$this->taskQueue->isEmpty()) {
            /** @var Task $task */
            $task = $this->taskQueue->dequeue();
            $retval = $task->run();

            if ($retval instanceof SystemCall) {
                $retval($task, $this);
                continue;
            }

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }

    public function killTask($tid)
    {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }

        unset($this->taskMap[$tid]);

        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return true;
    }

    public function waitForRead($socket, Task $task)
    {
        if (isset($this->waitingForRead[(int)$socket])) {
            $this->waitingForRead[(int)$socket][1][] = $task;
        } else {
            $this->waitingForRead[(int)$socket] = [$socket, [$task]];
        }
    }

    /**
     * @param      $socket
     * @param Task $task
     */
    public function waitForWrite($socket, Task $task)
    {
        if (isset($this->waitingForWrite[(int)$socket])) {
            $this->waitingForWrite[(int)$socket][1][] = $task;
        } else {
            $this->waitingForWrite[(int)$socket] = [$socket, [$task]];
        }
    }

    /**
     * @param int|null $timeout
     */
    protected function ioPoll($timeout)
    {
        $rSocks = [];
        foreach ($this->waitingForRead as [$socket]) {
            $rSocks[] = $socket;
        }

        $wSocks = [];
        foreach ($this->waitingForWrite as [$socket]) {
            $wSocks[] = $socket;
        }

        $eSocks = []; // dummy

        if (!(empty($rSocks) && empty($wSocks) && empty($eSocks))) {
            if (!stream_select($rSocks, $wSocks, $eSocks, $timeout)) {
                return;
            }
        }

        foreach ($rSocks as $socket) {
            [, $tasks] = $this->waitingForRead[(int)$socket];
            unset($this->waitingForRead[(int)$socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }

        foreach ($wSocks as $socket) {
            [, $tasks] = $this->waitingForWrite[(int)$socket];
            unset($this->waitingForWrite[(int)$socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }
    }

    /**
     * @return Generator
     */
    protected function ioPollTask(): Generator
    {
        while (true) {
            if ($this->taskQueue->isEmpty()) {
                $this->ioPoll(null);
            } else {
                $this->ioPoll(0);
            }
            yield;
        }
    }
}
