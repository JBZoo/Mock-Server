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

namespace JBZoo\PHPUnit;

class MockServerReadmeTest extends AbstractReadmeTest
{
    /** @var string */
    protected $packageName = 'Mock-Server';

    /** @var string[] */
    protected $badgesTemplate = [
        'travis',
        'docker_build',
        'coveralls',
        'psalm_coverage',
        'scrutinizer',
        'codefactor',
        'strict_types',
        '__BR__',
        'latest_stable_version',
        'dependents',
        'github_issues',
        'total_downloads',
        'docker_pulls',
        'github_license',
    ];

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->params = \array_merge($this->params, [
            'docker_build' => true,
            'docker_pulls' => true,
            'scrutinizer'  => true,
            'codefactor'   => true,
            'strict_types' => true,
        ]);

        parent::setUp();
    }
}
