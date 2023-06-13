<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoAbsoluteSymlinksExist
 *
 * @covers ::assertIsFulfilled
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 */
final class NoAbsoluteSymlinksExistUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function createSut(): NoAbsoluteSymlinksExist
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        return new NoAbsoluteSymlinksExist($fileFinder, $filesystem, $pathFactory, $translatableFactory, $translator);
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no absolute links in the codebase.';
    }
}
