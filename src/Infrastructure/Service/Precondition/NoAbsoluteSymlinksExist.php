<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoAbsoluteSymlinksExistInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 *
 * phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
 */
final class NoAbsoluteSymlinksExist extends AbstractFileIteratingPrecondition implements NoAbsoluteSymlinksExistInterface
{
    public function getName(): TranslatableInterface
    {
        return $this->t('No absolute links exist');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The codebase cannot contain absolute links.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('There are no absolute links in the codebase.');
    }

    protected function assertIsSupportedFile(
        string $codebaseName,
        PathInterface $codebaseRoot,
        PathInterface $file,
    ): void {
        if (!$this->filesystem->isSymlink($file)) {
            return;
        }

        $target = $this->filesystem->readLink($file);

        if ($target->isAbsolute()) {
            throw new PreconditionException(
                $this,
                $this->t(
                    'The %codebase_name directory at "%codebase_root" contains absolute links, '
                    . 'which is not supported. The first one is "%file".',
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
