<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer
 * @covers ::__construct
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface|\Prophecy\Prophecy\ObjectProphecy rsync
 */
class RsyncFileSyncerTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->rsync = $this->prophesize(RsyncRunnerInterface::class);
    }

    protected function createSut(): RsyncFileSyncer
    {
        $filesystem = $this->filesystem->reveal();
        $rsync = $this->rsync->reveal();
        return new RsyncFileSyncer($filesystem, $rsync);
    }

    /**
     * @dataProvider providerSync
     */
    public function testSync($source, $destination, $exclusions, $command, $callback): void
    {
        $this->filesystem
            ->mkdir($destination)
            ->shouldBeCalledOnce();
        $this->rsync
            ->run($command, $callback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->sync($source, $destination, $exclusions, $callback);
    }

    public function providerSync(): array
    {
        return [
            [
                'source' => 'lorem/ipsum',
                'destination' => 'dolor/sit',
                'exclusions' => [],
                'command' => [
                    '--archive',
                    '--delete-during',
                    '--verbose',
                    '--exclude=lorem/ipsum',
                    'lorem/ipsum' . DIRECTORY_SEPARATOR,
                    'dolor/sit' . DIRECTORY_SEPARATOR,
                ],
                'callback' => null,
            ],
            [
                'source' => 'ipsum/dolor' . DIRECTORY_SEPARATOR,
                'destination' => 'sit/amet',
                'exclusions' => [
                    'consectetur.txt',
                    'adipiscing.php',
                ],
                'command' => [
                    '--archive',
                    '--delete-during',
                    '--verbose',
                    '--exclude=consectetur.txt',
                    '--exclude=adipiscing.php',
                    '--exclude=ipsum/dolor' . DIRECTORY_SEPARATOR,
                    'ipsum/dolor' . DIRECTORY_SEPARATOR,
                    'sit/amet' . DIRECTORY_SEPARATOR,
                ],
                'callback' => new TestProcessOutputCallback(),
            ],
            [
                'source' => 'consectetur/adipiscing',
                'destination' => 'elit/sed',
                'exclusions' => [
                    'elit/sed',
                    'elit/sed',
                    'elit/sed',
                ],
                'command' => [
                    '--archive',
                    '--delete-during',
                    '--verbose',
                    '--exclude=elit/sed',
                    '--exclude=consectetur/adipiscing',
                    'consectetur/adipiscing' . DIRECTORY_SEPARATOR,
                    'elit/sed' . DIRECTORY_SEPARATOR,
                ],
                'callback' => null,
            ],
        ];
    }

    /**
     * @dataProvider providerSyncFailure
     */
    public function testSyncFailure($exception): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        $sut->sync('lorem', 'ipsum', []);
    }

    public function providerSyncFailure(): array
    {
        return [
            [IOException::class],
            [LogicException::class],
            [ProcessFailedException::class],
        ];
    }

    public function testSyncSourceDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(false);

        $sut = $this->createSut();

        $sut->sync(self::ACTIVE_DIR_DEFAULT, self::STAGING_DIR_DEFAULT);
    }

    public function testSyncCreateDestinationDirectoryFailed(): void
    {
        $this->expectException(IOException::class);

        $this->filesystem
            ->mkdir('destination')
            ->willThrow(IOException::class);

        $sut = $this->createSut();

        $sut->sync('source', 'destination');
    }
}
