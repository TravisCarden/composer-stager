<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirExists;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirExists
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirExists::__construct
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class StagingDirExistsUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
    }

    protected function createSut(): StagingDirExists
    {
        $filesystem = $this->filesystem->reveal();

        return new StagingDirExists($filesystem);
    }

    /**
     * @covers ::assertIsFulfilled
     * @covers ::isFulfilled
     */
    public function testIsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($stagingDir)
            ->willReturn(true);
        $sut = $this->createSut();

        self::assertEquals(true, $sut->isFulfilled($activeDir, $stagingDir));
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     * @covers ::isFulfilled
     */
    public function testIsUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($stagingDir)
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertFalse($isFulfilled);

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
