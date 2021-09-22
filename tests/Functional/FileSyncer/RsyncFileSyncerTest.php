<?php

namespace PhpTuf\ComposerStager\Tests\Functional\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer;
use PhpTuf\ComposerStager\Tests\Functional\FileSyncer\FileSyncerTestCase;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer
 * @covers ::__construct
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\Runner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil
 */
class RsyncFileSyncerTest extends FileSyncerTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!self::isRsyncAvailable()) {
            return;
        }
        self::createTestEnvironment(self::ACTIVE_DIR);
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::isRsyncAvailable()) {
            return;
        }
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        if (!self::isRsyncAvailable()) {
            self::markTestSkipped('Rsync is not available for testing.');
        }
    }

    protected static function isRsyncAvailable(): bool
    {
        $finder = new ExecutableFinder();
        return $finder->find('rsync') !== null;
    }

    protected function createSut(): FileSyncerInterface
    {
        $container = self::getContainer();

        /** @var RsyncFileSyncer $sut */
        $sut = $container->get(RsyncFileSyncer::class);
        return $sut;
    }
}
