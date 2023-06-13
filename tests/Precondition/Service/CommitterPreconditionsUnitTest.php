<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Domain\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Infrastructure\Precondition\Service\CommitterPreconditions;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\CommitterPreconditions
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Precondition\Service\CommonPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $commonPreconditions
 * @property \PhpTuf\ComposerStager\Domain\Precondition\Service\NoUnsupportedLinksExistInterface|\Prophecy\Prophecy\ObjectProphecy $noUnsupportedLinksExist
 * @property \PhpTuf\ComposerStager\Domain\Precondition\Service\StagingDirIsReadyInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirIsReady
 */
final class CommitterPreconditionsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->commonPreconditions = $this->prophesize(CommonPreconditionsInterface::class);
        $this->commonPreconditions
            ->getLeaves()
            ->willReturn([$this->commonPreconditions]);
        $this->noUnsupportedLinksExist = $this->prophesize(NoUnsupportedLinksExistInterface::class);
        $this->noUnsupportedLinksExist
            ->getLeaves()
            ->willReturn([$this->noUnsupportedLinksExist]);
        $this->stagingDirIsReady = $this->prophesize(StagingDirIsReadyInterface::class);
        $this->stagingDirIsReady
            ->getLeaves()
            ->willReturn([$this->stagingDirIsReady]);

        parent::setUp();
    }

    protected function createSut(): CommitterPreconditions
    {
        $commonPreconditions = $this->commonPreconditions->reveal();
        $noUnsupportedLinksExist = $this->noUnsupportedLinksExist->reveal();
        $stagingDirIsReady = $this->stagingDirIsReady->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new CommitterPreconditions($commonPreconditions, $noUnsupportedLinksExist, $stagingDirIsReady, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->commonPreconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noUnsupportedLinksExist
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirIsReady
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The preconditions for making staged changes live are fulfilled.');
    }

    public function testUnfulfilled(): void
    {
        $previous = self::createTestPreconditionException(__METHOD__);
        $this->commonPreconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->willThrow($previous);

        $this->doTestUnfulfilled($previous->getMessage());
    }
}
