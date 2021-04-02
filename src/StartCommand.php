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

use JBZoo\MockServer\Server\MockServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StartCommand
 * @package JBZoo\MockServer
 */
class StartCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $req = InputOption::VALUE_REQUIRED;
        $none = InputOption::VALUE_NONE;

        $this
            ->setName('start')
            ->setDescription('Try to connect to TransferWise account to check current keys and tokens')
            ->addOption('host', null, $req, "Host", MockServer::DEFAULT_HOST)
            ->addOption('port', null, $req, "Port", (string)MockServer::DEFAULT_PORT)
            ->addOption('mocks', null, $req, "Mocks path", './mocks')
            ->addOption('check-syntax', null, $none, 'Check syntax of PHP mock files before loading. ' .
                'It can take some time on loading');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new MockServer())
            ->setOutput($output)
            ->setHost(self::getOptionString('host', $input))
            ->setPort(self::getOptionInt('port', $input))
            ->setMocksPath(self::getOptionString('mocks', $input))
            ->setCheckSyntax(self::isOptionEnabled('check-syntax', $input))
            ->start();

        return 0;
    }

    /**
     * @param string         $option
     * @param InputInterface $input
     * @return int
     */
    private static function getOptionInt(string $option, InputInterface $input): int
    {
        return (int)implode('', (array)$input->getOption($option));
    }

    /**
     * @param string         $option
     * @param InputInterface $input
     * @return string
     */
    private static function getOptionString(string $option, InputInterface $input): string
    {
        return (string)implode('', (array)$input->getOption($option));
    }

    /**
     * @param string         $option
     * @param InputInterface $input
     * @return bool
     */
    private static function isOptionEnabled(string $option, InputInterface $input): bool
    {
        return (bool)$input->getOption($option);
    }
}
