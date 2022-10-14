<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\StagerPreconditions;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition\PreconditionTestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\StagerPreconditions
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $commonPreconditions
 * @property \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\StagingDirIsReadyInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirIsReady
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class StagerPreconditionsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->commonPreconditions = $this->prophesize(CommonPreconditionsInterface::class);
        $this->stagingDirIsReady = $this->prophesize(StagingDirIsReadyInterface::class);

        parent::setUp();
    }

    protected function createSut(): StagerPreconditions
    {
        $commonPreconditions = $this->commonPreconditions->reveal();
        $stagingDirIsReady = $this->stagingDirIsReady->reveal();

        return new StagerPreconditions($commonPreconditions, $stagingDirIsReady);
    }

    public function testFulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->commonPreconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(2);
        $this->stagingDirIsReady
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(2);

        parent::testFulfilled();
    }

    public function testUnfulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->commonPreconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(2)
            ->willThrow(PreconditionException::class);

        parent::testUnfulfilled();
    }
}
