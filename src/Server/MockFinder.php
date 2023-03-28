<?php

/**
 * JBZoo Toolbox - Mock-Server.
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @see        https://github.com/JBZoo/Mock-Server
 */

declare(strict_types=1);

namespace JBZoo\MockServer\Server;

use JBZoo\MockServer\Mocks\AbstractMock;
use JBZoo\MockServer\Mocks\JsonMock;
use JBZoo\MockServer\Mocks\PhpMock;
use JBZoo\MockServer\Mocks\YmlMock;
use JBZoo\Utils\FS;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class MockFinder
 * @package JBZoo\MockServer\Server
 */
class MockFinder
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $mocksPath;

    /**
     * @var bool
     */
    private $checkSyntax;

    /**
     * MockFinder constructor.
     * @param string          $mocksPath
     * @param LoggerInterface $logger
     * @param bool            $checkSyntax
     */
    public function __construct(string $mocksPath, LoggerInterface $logger, bool $checkSyntax)
    {
        $this->mocksPath = $mocksPath;
        $this->logger = $logger;
        $this->checkSyntax = $checkSyntax;
    }

    /**
     * @return AbstractMock[]
     */
    public function getMocks(): array
    {
        $relPath = FS::getRelative($this->mocksPath);
        $this->logger->info(str_replace($relPath, "<comment>{$relPath}</comment>", "Mocks Path: {$this->mocksPath}"));

        $types = [
            'php'  => PhpMock::class,
            'yml'  => YmlMock::class,
            'yaml' => YmlMock::class,
            'json' => JsonMock::class,
        ];

        $finder = (new Finder())
            ->in($this->mocksPath)
            ->files()
            ->ignoreDotFiles(true)
            ->followLinks();

        foreach (array_keys($types) as $fileExt) {
            $finder
                ->name(".{$fileExt}")
                ->name("*.{$fileExt}")
                ->name(".*.{$fileExt}");
        }

        $mocks = [];
        foreach ($finder as $file) {
            $filePath = $file->getPathname();
            $fileExt = $file->getExtension();

            $validationErrors = null;
            if ($this->checkSyntax) {
                $validationErrors = AbstractMock::isSourceValid($filePath);
            }

            if (!$validationErrors) {
                /** @var AbstractMock $mockClass */
                $mockClass = $types[$fileExt];
                $mock = new $mockClass($filePath);
                $mocks[$mock->getHash()] = $mock;
            } else {
                $this->logger->warning("Fixture \"{$filePath}\" is invalid and skipped\n{$validationErrors}");
            }
        }

        return $mocks;
    }
}
