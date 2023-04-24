<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition;
use Prophecy\Argument;

/**
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 */
abstract class FileIteratingPreconditionUnitTestCase extends PreconditionTestCase
{
    abstract protected function fulfilledStatusMessage(): string;

    protected function setUp(): void
    {
        $this->fileFinder = $this->prophesize(RecursiveFileFinderInterface::class);
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::type(PathInterface::class))
            ->willReturn(true);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);

        parent::setUp();
    }

    /** @covers \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition::exitEarly */
    public function testExitEarly(): void
    {
        $this->filesystem
            ->exists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();

        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();

        // Create a concrete implementation for testing since the SUT in
        // this case, being abstract, can't be instantiated directly.
        $sut = new class ($fileFinder, $filesystem, $pathFactory) extends AbstractFileIteratingPrecondition
        {
            protected function getDefaultUnfulfilledStatusMessage(): string
            {
                return '';
            }

            protected function isSupportedFile(PathInterface $file, PathInterface $codebaseRootDir): bool
            {
                return true;
            }

            protected function getFulfilledStatusMessage(): string
            {
                return '';
            }

            public function getName(): string
            {
                return '';
            }

            public function getDescription(): string
            {
                return '';
            }

            protected function exitEarly(
                PathInterface $activeDir,
                PathInterface $stagingDir,
                ?PathListInterface $exclusions,
            ): bool {
                return true;
            }
        };

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }

    /** @covers ::getUnfulfilledStatusMessage */
    public function testActiveDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $this->filesystem
            ->exists($this->activeDir)
            ->shouldBeCalled()
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $statusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /** @covers ::getUnfulfilledStatusMessage */
    public function testStagingDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $this->filesystem
            ->exists($this->stagingDir)
            ->shouldBeCalled()
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $statusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /** @covers ::getUnfulfilledStatusMessage */
    public function testNoFilesFound(): void
    {
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $statusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated empty codebase as fulfilled.');
    }

    /**
     * @covers ::getUnfulfilledStatusMessage
     *
     * @dataProvider providerFileFinderExceptions
     */
    public function testFileFinderExceptions(ExceptionInterface $exception): void
    {
        $this->expectException(PreconditionException::class);
        $this->expectExceptionMessage($exception->getMessage());

        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willThrow($exception);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled);

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }

    public function providerFileFinderExceptions(): array
    {
        return [
            [new InvalidArgumentException('Exclusions include invalid paths.')],
            [new IOException('The directory cannot be found or is not actually a directory.')],
        ];
    }

    public function assertFulfilled(bool $isFulfilled, string $statusMessage, string $assertionMessage): void
    {
        self::assertTrue($isFulfilled, $assertionMessage);
        self::assertSame($this->fulfilledStatusMessage(), $statusMessage, 'Got correct status message');
    }
}
