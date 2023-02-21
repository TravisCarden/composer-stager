<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use Prophecy\Argument;

/**
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 */
abstract class LinkIteratingPreconditionUnitTestCase extends PreconditionTestCase
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

    /**
     * @covers ::findFiles
     * @covers ::getUnfulfilledStatusMessage
     */
    public function testActiveDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($activeDir)
            ->shouldBeCalled()
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);
        $statusMessage = $sut->getStatusMessage($activeDir, $stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /**
     * @covers ::findFiles
     * @covers ::getUnfulfilledStatusMessage
     */
    public function testStagingDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($stagingDir)
            ->shouldBeCalled()
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);
        $statusMessage = $sut->getStatusMessage($activeDir, $stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /**
     * @covers ::findFiles
     * @covers ::getUnfulfilledStatusMessage
     */
    public function testNoFilesFound(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);
        $statusMessage = $sut->getStatusMessage($activeDir, $stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated empty codebase as fulfilled.');
    }

    /**
     * @covers ::findFiles
     * @covers ::getUnfulfilledStatusMessage
     *
     * @dataProvider providerFileFinderExceptions
     */
    public function testFileFinderExceptions(ExceptionInterface $exception): void
    {
        $this->expectException(PreconditionException::class);
        $this->expectExceptionMessage($exception->getMessage());

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willThrow($exception);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertFalse($isFulfilled);

        $sut->assertIsFulfilled($activeDir, $stagingDir);
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
