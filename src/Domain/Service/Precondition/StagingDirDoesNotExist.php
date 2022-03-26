<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class StagingDirDoesNotExist extends AbstractPrecondition implements StagingDirDoesNotExistInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    public static function getName(): string
    {
        return 'Staging directory does not exist'; // @codeCoverageIgnore
    }

    public static function getDescription(): string
    {
        return 'The staging directory must not already exist before beginning the staging process.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return !$this->filesystem->exists($stagingDir->resolve());
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The staging directory does not already exist.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The staging directory already exists.'; // @codeCoverageIgnore
    }
}
