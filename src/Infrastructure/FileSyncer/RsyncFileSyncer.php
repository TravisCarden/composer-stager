<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Util\DirectoryUtil;

/**
 * @internal
 */
final class RsyncFileSyncer implements RsyncFileSyncerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface
     */
    private $rsync;

    public function __construct(FilesystemInterface $filesystem, RsyncRunnerInterface $rsync)
    {
        $this->filesystem = $filesystem;
        $this->rsync = $rsync;
    }

    public function sync(
        string $from,
        string $to,
        ?array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        if (!$this->filesystem->exists($from)) {
            throw new DirectoryNotFoundException($from, 'The "sync from" directory does not exist at "%s"');
        }

        $command = [
            // Archive mode--the same as -rlptgoD (no -H), or --recursive,
            // --links, --perms, --times, --group, --owner, --devices, --specials.
            '--archive',
            // Delete extraneous files from destination directories.
            '--delete',
            // Increase verbosity.
            '--verbose',
        ];

        foreach ((array) $exclusions as $exclusion) {
            $command[] = '--exclude=' . $exclusion;
        }

        // A trailing slash is added to the "from" directory so the CONTENTS of
        // the active directory are synced, not the directory itself.
        $command[] = DirectoryUtil::ensureTrailingSlash($from);
        $command[] = $to;

        try {
            $this->rsync->run($command, $callback);
        } catch (ExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
