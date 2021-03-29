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
            ->addOption('port', null, $req, "Port", MockServer::DEFAULT_PORT)
            ->addOption('mocks', null, $req, "Mocks path", './mocks')
            ->addOption('check-php-syntax', null, $none, 'Check syntax of PHP mock files before loading. ' .
                'It can take some time on loading');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new MockServer())
            ->setOutput($output)
            ->setHost((string)$input->getOption('host'))
            ->setPort((int)$input->getOption('port'))
            ->setMocksPath((string)$input->getOption('mocks'))
            ->start();

        return 0;
    }
}
