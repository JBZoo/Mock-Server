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

use JBZoo\Utils\FS;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class ConsoleLogger
 * PSR-3 compliant console logger.
 * @package JBZoo\MockServer\Server
 *
 * @see     https://www.php-fig.org/psr/psr-3/
 * @author  Kévin Dunglas <dunglas@gmail.com>
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
        LogLevel::INFO      => OutputInterface::VERBOSITY_VERBOSE,
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
                $output = $this->output->getErrorOutput();
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
        $replacements = [];
        foreach ($context as $key => $val) {
            if ($val instanceof Throwable) {
                $replacements["{{$key}}"] = $this->prettyPrintException($val);
            } elseif (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
            } elseif (\is_object($val)) {
                $replacements["{{$key}}"] = '[object ' . \get_class($val) . ']';
            } else {
                $replacements["{{$key}}"] = '[' . \gettype($val) . ']';
            }
        }

        return trim($message . "\n" . implode(' ', $replacements));
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    private function prettyPrintException(Throwable $exception): string
    {
        $message = [
            "  Code #{$exception->getCode()}; {$exception->getMessage()}",
            "  File: " . self::getRelativePath($exception->getFile(), $exception->getLine()),
        ];

        if ($this->output->isVeryVerbose()) {
            $message[] = "  Stack trace:\n" . self::dumpTrace($exception->getTrace());
        }

        return implode("\n", $message);
    }

    /**
     * @param array $trace
     * @return string
     */
    private static function dumpTrace(array $trace): string
    {
        $result = [];
        foreach ($trace as $key => $traceRow) {
            $result[] = self::getOneTrace($traceRow);
        }

        return "  - " . implode("\n  - ", $result);
    }

    /**
     * Get formated one trace info
     * @param array $traceRow One trace element
     * @return string
     */
    private static function getOneTrace(array $traceRow): string
    {
        $function = null;
        $file = isset($traceRow['file'])
            ? self::getRelativePath($traceRow['file'], $traceRow['line'])
            : null;

        $isIncluding = in_array($traceRow['function'], ['include', 'include_once', 'require', 'require_once'], true);

        if ($isIncluding) {
            $includedFile = self::getRelativePath($traceRow['args'][0] ?? '');
            $function = "{$traceRow['function']} ('{$includedFile}')";
        } elseif (isset($traceRow['type'], $traceRow['class'])) {
            $function = "{$traceRow['class']}{$traceRow['type']}{$traceRow['function']}()";
        } else {
            $function = $traceRow['function'] . '()';
        }

        return trim("{$file} <comment>{$function}</comment>");
    }

    /**
     * @param string   $filepath
     * @param int|null $line
     * @return string
     */
    private static function getRelativePath(string $filepath, ?int $line = null): string
    {
        $lineFormated = $line > 0 ? ":{$line}" : '';
        $filename = pathinfo($filepath, PATHINFO_BASENAME) . $lineFormated;

        $relPath = str_replace(
            $filename,
            "<filename>{$filename}</filename>",
            './' . FS::getRelative($filepath) . $lineFormated
        );

        $relPath = \strpos($relPath, './vendor/') === 0 ? $relPath : "<info>{$relPath}</info>";

        return $relPath;
    }
}
