<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Domain;

use PhpTuf\ComposerStager\Domain\Cleaner;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Cleaner
 * @covers \PhpTuf\ComposerStager\Domain\Cleaner::__construct
 * @uses \PhpTuf\ComposerStager\Domain\Cleaner::directoryExists
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy filesystem
 */
class CleanerTest extends TestCase
{
    private const STAGING_DIR = '/lorem/ipsum';

    public function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->willReturn(true);
    }

    public function createSut(): Cleaner
    {
        $filesystem = $this->filesystem->reveal();
        return new Cleaner($filesystem);
    }

    /**
     * @covers ::clean
     */
    public function testCleanHappyPath(): void
    {
        $this->filesystem
            ->remove(static::STAGING_DIR)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean(static::STAGING_DIR);
    }

    /**
     * @covers ::clean
     */
    public function testCleanDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/staging directory.*exist/');

        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->willReturn(false);
        $this->filesystem
            ->remove(static::STAGING_DIR)
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $sut->clean(static::STAGING_DIR);
    }

    /**
     * @covers ::directoryExists
     *
     * @dataProvider providerDirectoryExists
     */
    public function testDirectoryExists($expected): void
    {
        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->directoryExists(static::STAGING_DIR);

        self::assertSame($expected, $actual, 'Correctly detected existence of staging directory.');
    }

    public function providerDirectoryExists(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @covers ::clean
     */
    public function testCleanFailToRemove(): void
    {
        $this->expectException(IOException::class);

        $this->filesystem
            ->remove(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willThrow(new \Symfony\Component\Filesystem\Exception\IOException(''));
        $sut = $this->createSut();

        $sut->clean(static::STAGING_DIR);
    }
}
