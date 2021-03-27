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
 * Class Task
 */
class Task
{
    /**
     * @var int
     */
    protected $taskId;

    /**
     * @var Generator
     */
    protected $coroutine;

    /**
     * @var int|string|null
     */
    protected $sendValue = null;

    protected $beforeFirstYield = true;

    public function __construct($taskId, Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setSendValue($sendValue): void
    {
        $this->sendValue = $sendValue;
    }

    public function run()
    {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        }

        $retValue = $this->coroutine->send($this->sendValue);
        $this->sendValue = null;

        return $retValue;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return !$this->coroutine->valid();
    }
}
