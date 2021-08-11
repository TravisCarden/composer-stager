<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Domain;

use PhpTuf\ComposerStager\Domain\Committer;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Committer
 * @covers \PhpTuf\ComposerStager\Domain\Committer::__construct
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface|\Prophecy\Prophecy\ObjectProphecy fileCopier
 */
class CommitterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->fileCopier = $this->prophesize(FileCopierInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->isWritable(Argument::any())
            ->willReturn(true);
    }

    protected function createSut(): Committer
    {
        $fileCopier = $this->fileCopier->reveal();
        $filesystem = $this->filesystem->reveal();
        return new Committer($fileCopier, $filesystem);
    }

    /**
     * @covers ::commit
     */
    public function testCommitWithMinimumParams(): void
    {
        $this->fileCopier
            ->copy(self::STAGING_DIR_DEFAULT, self::ACTIVE_DIR_DEFAULT, [], Argument::cetera())
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit(self::STAGING_DIR_DEFAULT, self::ACTIVE_DIR_DEFAULT);
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerCommitWithOptionalParams
     */
    public function testCommitWithOptionalParams($stagingDir, $activeDir, $callback, $timeout): void
    {
        $this->fileCopier
            ->copy($stagingDir, $activeDir, [], $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit($stagingDir, $activeDir, $callback, $timeout);
    }

    public function providerCommitWithOptionalParams(): array
    {
        return [
            [
                'stagingDir' => '/lorem/ipsum',
                'activeDir' => '/dolor/sit',
                'callback' => null,
                'timeout' => null,
            ],
            [
                'stagingDir' => 'amet/consectetur',
                'activeDir' => 'adipiscing/elit',
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerDirectoryNotFound
     */
    public function testDirectoryNotFound($stagingDir, $activeDir, $missingDir, $exceptionMessage): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches($exceptionMessage);
        $this->filesystem
            ->exists($missingDir)
            ->willReturn(false);
        $this->fileCopier
            ->copy(Argument::cetera())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $sut->commit($stagingDir, $activeDir);
    }

    public function providerDirectoryNotFound(): array
    {
        return [
            [
                'stagingDir' => '/lorem/ipsum/staging',
                'activeDir' => '/dolor/sit/active',
                'missingDir' => '/dolor/sit/active',
                'exceptionMessage' => '@active directory.*not exist.*/active@',
            ],
            [
                'stagingDir' => 'amet/consectetur/staging',
                'activeDir' => 'adipiscing/elit/active',
                'missingDir' => 'amet/consectetur/staging',
                'exceptionMessage' => '@staging directory.*not exist.*staging@',
            ],
        ];
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerActiveDirectoryNotWritable
     */
    public function testActiveDirectoryNotWritable($activeDir): void
    {
        $this->expectException(DirectoryNotWritableException::class);
        $this->expectExceptionMessageMatches(sprintf('@active directory.*not writable.*%s@', addslashes($activeDir)));
        $this->filesystem
            ->isWritable($activeDir)
            ->willReturn(false);
        $this->fileCopier
            ->copy(Argument::cetera())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $sut->commit($activeDir, $activeDir);
    }

    public function providerActiveDirectoryNotWritable(): array
    {
        return [
            ['activeDir' => '/lorem/ipsum'],
            ['activeDir' => '/dolor/sit'],
        ];
    }

    /**
     * @covers ::directoryExists
     *
     * @dataProvider providerDirectoryExists
     */
    public function testDirectoryExists($expected): void
    {
        $this->filesystem
            ->exists(static::STAGING_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->directoryExists(static::STAGING_DIR_DEFAULT);

        self::assertSame($expected, $actual, 'Correctly detected existence of staging directory.');
    }

    public function providerDirectoryExists(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
