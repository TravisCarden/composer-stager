<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @property \PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 */
final class NoSymlinksPointOutsideTheCodebaseUnitTest extends FileIteratingPreconditionUnitTestCase
{
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

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no symlinks that point outside the codebase.';
    }

    protected function createSut(): NoSymlinksPointOutsideTheCodebase
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new NoSymlinksPointOutsideTheCodebase($fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }
}
