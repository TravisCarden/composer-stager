<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;

/**
 * Begins the staging process by copying the active directory to the staging directory.
 */
interface BeginnerInterface
{
    /**
     * Begins the staging process.
     *
     * @param string $activeDir
     *   The active directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/public" or "public".
     * @param string $stagingDir
     *   The staging directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/staging" or "staging".
     * @param string[]|null $exclusions
     *   Paths to exclude, relative to the active directory. Careful use of
     *   exclusions can reduce execution time and disk usage. Two kinds of files
     *   and directories are good candidates for exclusion:
     *   - Those that will (or might) be changed in the active directory between
     *     beginning and committing and should not be overwritten in the active
     *     directory. This might include user upload directories, for example.
     *   - Those that definitely will NOT be changed or needed between beginning
     *     and committing and will therefore have no effect on the final outcome.
     *     This might include your version control directory, e.g., ".git", or
     *     certain kinds of caches, e.g., of HTTP responses.
     *
     *   With rare exception, you should use the same exclusions when beginning
     *   as when committing.
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @see \PhpTuf\ComposerStager\Domain\CommitterInterface::commit
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
     *   If the staging directory already exists.
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the active directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function begin(
        string $activeDir,
        string $stagingDir,
        ?array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;
}
