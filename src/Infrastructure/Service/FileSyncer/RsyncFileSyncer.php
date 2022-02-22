<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;

final class RsyncFileSyncer implements FileSyncerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var \PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface
     */
    private $rsync;

    public function __construct(FilesystemInterface $filesystem, RsyncRunnerInterface $rsync)
    {
        $this->filesystem = $filesystem;
        $this->rsync = $rsync;
    }

    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
    public function sync(
        PathInterface $source,
        PathInterface $destination,
        PathListInterface $exclusions = null,
        ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        $exclusions = $exclusions ?? new PathList([]);
        $exclusions = $exclusions->getAll();

        if (!$this->filesystem->exists($source->resolve())) {
            throw new DirectoryNotFoundException($source->resolve(), 'The source directory does not exist at "%s"');
        }

        $command = [
            // Archive mode--the same as -rlptgoD (no -H), or --recursive,
            // --links, --perms, --times, --group, --owner, --devices, --specials.
            '--archive',
            // Delete extraneous files from destination directories. Note: Using
            // --delete-after rather than alternatives prevents "file has
            // vanished" errors when syncing a directory with its own ancestor.
            '--delete-after',
            // Increase verbosity.
            '--verbose',
        ];

        // Prevent infinite recursion if the source is inside the destination.
        $exclusions[] = $source->resolve();

        // There's no reason to process duplicates.
        $exclusions = array_unique($exclusions);

        foreach ($exclusions as $exclusion) {
            $command[] = '--exclude=' . $exclusion;
        }

        // A trailing slash is added to the source directory so the CONTENTS
        // of the directory are synced, not the directory itself.
        $command[] = $source->resolve() . DIRECTORY_SEPARATOR;

        $command[] = $destination->resolve();

        // Ensure the destination directory's existence. (This has no effect
        // if it already exists.)
        $this->filesystem->mkdir($destination->resolve());
        try {
            $this->rsync->run($command, $callback);
        } catch (ExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
