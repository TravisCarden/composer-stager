<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\Domain\Service\Translation\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Host\HostInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class NoLinksExistOnWindows extends AbstractFileIteratingPrecondition implements NoLinksExistOnWindowsInterface
{
    public function __construct(
        RecursiveFileFinderInterface $fileFinder,
        FilesystemInterface $filesystem,
        private readonly HostInterface $host,
        PathFactoryInterface $pathFactory,
        TranslatableFactoryInterface $translatableFactory,
        TranslatorInterface $translator,
    ) {
        parent::__construct($fileFinder, $filesystem, $pathFactory, $translatableFactory, $translator);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('No links exist on Windows');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The codebase cannot contain links if on Windows.');
    }

    protected function exitEarly(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions,
    ): bool {
        // This is a Windows-specific precondition. No need to run it anywhere else.
        return !$this->host::isWindows();
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('There are no links in the codebase if on Windows.');
    }

    /**
     * @codeCoverageIgnore This code is host-specific, so it shouldn't be counted against
     *   code coverage numbers. Nevertheless, it IS covered by tests on Windows-based CI jobs.
     */
    protected function assertIsSupportedFile(
        string $codebaseName,
        PathInterface $codebaseRoot,
        PathInterface $file,
    ): void {
        if ($this->filesystem->isLink($file)) {
            throw new PreconditionException(
                $this,
                $this->t(
                    'The %codebase_name directory at %codebase_root contains links, '
                    . 'which is not supported on Windows. The first one is %file.',
                    $this->p([
                        '%codebase_name' => $codebaseName,
                        '%codebase_root' => $codebaseRoot->resolved(),
                        '%file' => $file->resolved(),
                    ]),
                ),
            );
        }
    }
}
