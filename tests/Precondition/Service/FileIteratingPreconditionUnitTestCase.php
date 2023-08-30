<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition */
abstract class FileIteratingPreconditionUnitTestCase extends PreconditionTestCase
{
    abstract protected function fulfilledStatusMessage(): string;

    protected EnvironmentInterface|ObjectProphecy $environment;
    protected FileFinderInterface|ObjectProphecy $fileFinder;
    protected FilesystemInterface|ObjectProphecy $filesystem;
    protected PathFactoryInterface|ObjectProphecy $pathFactory;

    protected function setUp(): void
    {
        $this->fileFinder = $this->prophesize(FileFinderInterface::class);
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

    /** @covers ::exitEarly */
    public function testExitEarly(): void
    {
        $this->filesystem
            ->exists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();

        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        // Create a concrete implementation for testing since the SUT in
        // this case, being abstract, can't be instantiated directly.
        $sut = new class ($environment, $fileFinder, $filesystem, $pathFactory, $translatableFactory) extends AbstractFileIteratingPrecondition
        {
            // @phpcs:ignore SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction
            protected function assertIsSupportedFile(
                string $codebaseName,
                PathInterface $codebaseRoot,
                PathInterface $file,
            ): void {
            }

            protected function getFulfilledStatusMessage(): TranslatableInterface
            {
                return new TestTranslatableMessage();
            }

            public function getName(): TranslatableInterface
            {
                return new TestTranslatableMessage();
            }

            public function getDescription(): TranslatableInterface
            {
                return new TestTranslatableMessage();
            }

            protected function exitEarly(
                PathInterface $activeDir,
                PathInterface $stagingDir,
                ?PathListInterface $exclusions,
            ): bool {
                return true;
            }
        };

        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testActiveDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->filesystem
            ->exists($activeDirPath)
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        $statusMessage = $sut->getStatusMessage($activeDirPath, $stagingDirPath);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /** @covers ::doAssertIsFulfilled */
    public function testStagingDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->filesystem
            ->exists($stagingDirPath)
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        $statusMessage = $sut->getStatusMessage($activeDirPath, $stagingDirPath);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /** @covers ::doAssertIsFulfilled */
    public function testNoFilesFound(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        $statusMessage = $sut->getStatusMessage($activeDirPath, $stagingDirPath);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated empty codebase as fulfilled.');
    }

    /**
     * @covers ::doAssertIsFulfilled
     *
     * @dataProvider providerFileFinderExceptions
     */
    public function testFileFinderExceptions(ExceptionInterface $previous): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willThrow($previous);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertFalse($isFulfilled);

        try {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        } catch (Throwable $e) {
            self::assertInstanceOf(PreconditionException::class, $e);
            self::assertSame($e->getMessage(), $previous->getMessage());
            self::assertInstanceOf($previous::class, $e->getPrevious());
        }
    }

    public function providerFileFinderExceptions(): array
    {
        return [
            'InvalidArgumentException' => [new InvalidArgumentException(new TestTranslatableMessage('Exclusions include invalid paths.'))],
            'IOException' => [new IOException(new TestTranslatableMessage('The directory cannot be found or is not actually a directory.'))],
        ];
    }

    /** @covers ::doAssertIsFulfilled */
    public function assertFulfilled(
        bool $isFulfilled,
        TranslatableInterface $statusMessage,
        string $assertionMessage,
    ): void {
        self::assertTrue($isFulfilled, $assertionMessage);
        self::assertEquals($this->fulfilledStatusMessage(), $statusMessage, 'Got correct status message');
    }
}
