<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Monitor\ChangeDetectionStrategy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Testcase for the Modification Time Change Detection Strategy
 *
 */
final class ModificationTimeStrategyTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @var \Neos\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy
     */
    protected $strategy;

    /**
     */
    protected function setUp(): void
    {
        vfsStream::setup('testDirectory');

        $cache = $this->createMock(\Neos\Cache\Frontend\StringFrontend::class);

        $this->strategy = new \Neos\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy();
        $this->strategy->injectCache($cache);
    }

    /**
     * @test
     */
    public function getFileStatusReturnsStatusUnchangedIfFileDoesNotExistAndDidNotExistEarlier()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';

        $status = $this->strategy->getFileStatus($fileUrl);
        self::assertSame(\Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
    }

    /**
     * @test
     */
    public function getFileStatusReturnsStatusUnchangedIfFileExistedAndTheModificationTimeDidNotChange()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        clearstatcache();
        $status = $this->strategy->getFileStatus($fileUrl);

        self::assertSame(\Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
    }

    /**
     * @test
     */
    public function getFileStatusDetectsANewlyCreatedFile()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $status = $this->strategy->getFileStatus($fileUrl);
        self::assertSame(\Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED, $status);
    }

    /**
     * @test
     */
    public function getFileStatusDetectsADeletedFile()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        unlink($fileUrl);
        $status = $this->strategy->getFileStatus($fileUrl);

        self::assertSame(\Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED, $status);
    }

    /**
     * @test
     */
    public function getFileStatusReturnsStatusChangedIfTheFileExistedEarlierButTheModificationTimeHasChangedSinceThen()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        vfsStreamWrapper::getRoot()->getChild('test.txt')->lastModified(time() + 5);
        clearstatcache();
        $status = $this->strategy->getFileStatus($fileUrl);

        self::assertSame(\Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CHANGED, $status);
    }
}
