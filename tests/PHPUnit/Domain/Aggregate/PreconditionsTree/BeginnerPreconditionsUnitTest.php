<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\BeginnerPreconditions;
use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\BeginnerPreconditions
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $commonPreconditions
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirDoesNotExist
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class BeginnerPreconditionsUnitTest extends TestCase
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
        $this->commonPreconditions = $this->prophesize(CommonPreconditionsInterface::class);
        $this->stagingDirDoesNotExist = $this->prophesize(StagingDirDoesNotExistInterface::class);
    }

    protected function createSut(): BeginnerPreconditions
    {
        $commonPreconditions = $this->commonPreconditions->reveal();
        $stagingDirDoesNotExist = $this->stagingDirDoesNotExist->reveal();
        return new BeginnerPreconditions($commonPreconditions, $stagingDirDoesNotExist);
    }

    /**
     * @covers ::__construct
     * @covers ::isFulfilled
     */
    public function testIsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->commonPreconditions
            ->isFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->stagingDirDoesNotExist
            ->isFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $sut = $this->createSut();

        self::assertTrue($sut->isFulfilled($activeDir, $stagingDir));
    }

    /**
     * @covers ::__construct
     * @covers ::isFulfilled
     */
    public function testIsUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->commonPreconditions
            ->isFulfilled($activeDir, $stagingDir)
            ->willReturn(false);
        $this->stagingDirDoesNotExist
            ->isFulfilled($activeDir, $stagingDir)
            ->willReturn(false);

        $sut = $this->createSut();

        self::assertFalse($sut->isFulfilled($activeDir, $stagingDir));

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
