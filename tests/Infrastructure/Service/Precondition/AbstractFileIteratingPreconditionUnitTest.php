<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 *
 * @covers ::__construct
 * @covers ::findFiles
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 */
final class AbstractFileIteratingPreconditionUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function fulfilledStatusMessage(): string
    {
        return '';
    }

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

    protected function createSut(): PreconditionInterface
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        // Create a concrete implementation for testing since the SUT in
        // this case, being abstract, can't be instantiated directly.
        return new class ($fileFinder, $filesystem, $pathFactory, $translatableFactory, $translator) extends AbstractFileIteratingPrecondition
        {
            public bool $exitEarly = false;

            // phpcs:ignore SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction
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
                return $this->exitEarly;
            }
        };
    }

    /**
     * @covers ::assertIsFulfilled
     * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition::exitEarly
     */
    public function testExitEarly(): void
    {
        $this->filesystem
            ->exists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();
        $sut = $this->createSut();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $sut->exitEarly = true;

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }
}
