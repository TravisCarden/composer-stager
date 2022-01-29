<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;

final class Cleaner implements CleanerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface
     */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function clean(
        PathInterface $stagingDir,
        OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $stagingDirResolved = $stagingDir->getResolved();
        if (!$this->directoryExists($stagingDir)) {
            throw new DirectoryNotFoundException($stagingDirResolved, 'The staging directory does not exist at "%s"');
        }

        $this->filesystem->remove($stagingDirResolved, $callback, $timeout);
    }

    public function directoryExists(PathInterface $stagingDir): bool
    {
        return $this->filesystem->exists($stagingDir->getResolved());
    }
}
