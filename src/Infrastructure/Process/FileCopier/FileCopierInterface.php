<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;

/**
 * Copies files from one location to another.
 */
interface FileCopierInterface
{
    /**
     * Copies files from one location to another.
     *
     * Files in the "to" directory will be overwritten by those in the "from"
     * directory, even if newer. Files in the "to" directory that do not exist
     * in the "from" directory will be deleted. Excluded paths will be completely
     * ignored and neither copied to nor deleted from the "to" directory.
     *
     * @param string $from
     *   The directory to copy files from, as an absolute path or relative to the
     *   current working directory (CWD), e.g., "/var/www/from" or "example".
     * @param string $to
     *   The directory to copy files to, as an absolute path or relative to the
     *   current working directory (CWD), e.g., "/var/www/to" or "example". If
     *   it does not exist it will be created.
     * @param string[]|null $exclusions
     *   An array of paths to exclude, relative to the "from" directory.
     *   (Absolute paths are not supported.)
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the "from" directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function copy(
        string $from,
        string $to,
        ?array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;
}
