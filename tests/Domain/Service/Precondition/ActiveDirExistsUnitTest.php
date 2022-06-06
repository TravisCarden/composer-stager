<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirExists;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirExists
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirExists::__construct
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class ActiveDirExistsUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->activeDir
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->stagingDir
            ->resolve()
            ->willReturn(self::STAGING_DIR);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
    }

    protected function createSut(): ActiveDirExists
    {
        $filesystem = $this->filesystem->reveal();

        return new ActiveDirExists($filesystem);
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     * @covers ::isFulfilled
     */
    public function testIsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($activeDir)
            ->shouldBeCalledOnce()
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
            ->exists($activeDir)
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertFalse($isFulfilled);

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
