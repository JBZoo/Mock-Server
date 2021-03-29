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

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * PSR-3 compliant console logger.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @see    https://www.php-fig.org/psr/psr-3/
 */
class ConsoleLogger extends AbstractLogger
{
    public const INFO    = 'info';
    public const ERROR   = 'error';
    public const DEBUG   = 'debug';
    public const WARNING = 'comment';

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var int[]
     */
    private $verbosityLevelMap = [
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT     => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL  => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR     => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING   => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::NOTICE    => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::INFO      => OutputInterface::VERBOSITY_VERY_VERBOSE,
        LogLevel::DEBUG     => OutputInterface::VERBOSITY_DEBUG,
    ];

    /**
     * @var string[]
     */
    private $formatLevelMap = [
        LogLevel::EMERGENCY => self::ERROR,
        LogLevel::ALERT     => self::ERROR,
        LogLevel::CRITICAL  => self::ERROR,
        LogLevel::ERROR     => self::ERROR,
        LogLevel::WARNING   => self::WARNING,
        LogLevel::NOTICE    => self::INFO,
        LogLevel::INFO      => self::INFO,
        LogLevel::DEBUG     => self::DEBUG,
    ];

    /**
     * @var bool
     */
    private $errored = false;

    /**
     * ConsoleLogger constructor.
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        $output->getFormatter()->setStyle('debug', new OutputFormatterStyle('cyan'));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('yellow'));
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        if (!isset($this->verbosityLevelMap[$level])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        $output = $this->output;

        // Write to the error output if necessary and available
        if (self::ERROR === $this->formatLevelMap[$level]) {
            if ($this->output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput();
            }
            $this->errored = true;
        }

        // the if condition check isn't necessary -- it's the same one that $output will do internally anyway.
        // We only do it for efficiency here as the message formatting is relatively expensive.
        if ($output->getVerbosity() >= $this->verbosityLevelMap[$level]) {
            $output->writeln(sprintf(
                '<%1$s>%2$s</%1$s>: %3$s',
                $this->formatLevelMap[$level],
                $level,
                $this->interpolate($message, $context)
            ), $this->verbosityLevelMap[$level]);
        }
    }

    /**
     * Returns true when any messages have been logged at error levels.
     * @return bool
     */
    public function hasErrored(): bool
    {
        return $this->errored;
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message
     * @param array  $context
     * @return string
     *
     * @author PHP Framework Interoperability Group
     */
    private function interpolate(string $message, array $context): string
    {
        if (false === strpos($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
            } elseif (\is_object($val)) {
                $replacements["{{$key}}"] = '[object ' . \get_class($val) . ']';
            } else {
                $replacements["{{$key}}"] = '[' . \gettype($val) . ']';
            }
        }

        return strtr($message, $replacements);
    }
}
