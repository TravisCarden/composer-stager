<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirIsReady;
use PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation\TestTranslatableFactory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirIsReady
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirExists
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirIsWritable
 */
final class StagingDirIsReadyUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->stagingDirExists = $this->prophesize(StagingDirExistsInterface::class);
        $this->stagingDirIsWritable = $this->prophesize(StagingDirIsWritableInterface::class);
        $this->stagingDirExists
            ->getLeaves()
            ->willReturn([$this->stagingDirExists]);
        $this->stagingDirIsWritable
            ->getLeaves()
            ->willReturn([$this->stagingDirIsWritable]);

        parent::setUp();
    }

    protected function createSut(): StagingDirIsReady
    {
        $stagingDirExists = $this->stagingDirExists->reveal();
        $stagingDirIsWritable = $this->stagingDirIsWritable->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new StagingDirIsReady($stagingDirExists, $stagingDirIsWritable, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->stagingDirExists
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirIsWritable
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The staging directory is ready to use.');
    }

    public function testUnfulfilled(): void
    {
        $message = 'The staging directory is not ready to use.';
        $previous = self::createTestPreconditionException($message);
        $this->stagingDirExists
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableMessage($message, $sut->getStatusMessage($this->activeDir, $this->stagingDir, $this->exclusions));
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        }, PreconditionException::class, $message);
    }
}
