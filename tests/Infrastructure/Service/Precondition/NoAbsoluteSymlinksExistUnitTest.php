<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteSymlinksExist;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteSymlinksExist
 *
 * @covers ::assertIsFulfilled
 * @covers ::exitEarly
 * @covers ::getDefaultUnfulfilledStatusMessage
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 */
final class NoAbsoluteSymlinksExistUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function createSut(): NoAbsoluteSymlinksExist
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();

        return new NoAbsoluteSymlinksExist($fileFinder, $filesystem, $pathFactory);
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no absolute links in the codebase.';
    }
}
