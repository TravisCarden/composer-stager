<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Filesystem\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;

/**
 * Provides basic utilities for interacting with the file system.
 *
 * @package Filesystem
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface FilesystemInterface
{
    /**
     * Changes the mode (permissions) of a given file.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   The file to change the mode on.
     * @param int $permissions
     *   The permissions for the file according to the rules at
     *   {@see https://www.php.net/manual/en/function.chmod.php}.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *    If the mode cannot be changed.
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *    If the file does not exist.
     */
    public function chmod(PathInterface $path, int $permissions): void;

    /**
     * Copies a given file from one place to another.
     *
     * If the file already exists at the destination it will be overwritten.
     * Copying directories is not supported.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $source
     *   The file to copy.
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $destination
     *   The file to copy to. If it does not exist it will be created.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the file cannot be copied.
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the source file does not exist, is not actually a file, or is the
     *   same as the destination.
     */
    public function copy(PathInterface $source, PathInterface $destination): void;

    /**
     * Determines whether the given path exists.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     */
    public function exists(PathInterface $path): bool;

    /**
     * Gets file permissions for a given path.
     *
     * This function returns permissions exactly as PHP's built-in `fileperms()` function, which is not necessarily
     * intuitive. Specifically, it returns an integer with no obvious relationship to any value usable with `chmod()`.
     *
     * For logical and comparison purposes, use an octal value:
     *   ```php
     *   chmod($file, 0644);
     *   $permissions = $sut->filePerms($file);
     *   $octal = $permissions & 0777; // This is always and only 0777.
     *   assert($octal === 0644); // true
     *   ```
     *
     * For human-readable display purposes, use a string:
     *   ```php
     *   chmod($file, 0644);
     *   $permissions = $sut->filePerms($file);
     *   $string = substr(sprintf('%o', $permissions), -4)
     *   assert($string === "0644"); // true
     *   ```
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   The path to get permissions for.
     *
     * @return int
     *   Returns the file's permissions. See above.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *    If case of failure.
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *    If the file does not exist.
     *
     * @see https://www.php.net/manual/en/function.fileperms.php for more on return values.
     */
    public function filePerms(PathInterface $path): int;

    /**
     * Determines whether the given path is a directory.
     *
     * Unlike PHP's built-in is_dir() function, this method distinguishes
     * between directories and LINKS to directories. In other words, if the path
     * is a link, even if the target is a directory, this method will return false.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the path exists and is a directory.
     */
    public function isDir(PathInterface $path): bool;

    /**
     * Determines whether the given directory is empty.
     *
     * @return bool
     *   Returns true if the directory is empty, false otherwise.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the directory does not exist or is not actually a directory.
     */
    public function isDirEmpty(PathInterface $path): bool;

    /**
     * Determines whether the given path is a regular file.
     *
     * Unlike PHP's built-in is_file() function, this method distinguishes
     * between regular files and LINKS to files. In other words, if the path is
     * a link, even if the target is a regular file, this method will return false.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the path exists and is a regular file.
     */
    public function isFile(PathInterface $path): bool;

    /**
     * Determines whether the given path is a hard link.
     *
     * Symbolic links (symlinks) are distinct from hard links and do not count.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the filename exists and is a hard link (not a symlink)
     *   false otherwise.
     */
    public function isHardLink(PathInterface $path): bool;

    /**
     * Determines whether the given path is a link.
     *
     * Symbolic links (symlinks) and hard links both count.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the filename exists and is a link, false otherwise.
     */
    public function isLink(PathInterface $path): bool;

    /**
     * Determines whether the given path is a symbolic link.
     *
     * Hard links are distinct from symbolic links (symlinks) and do not count.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the filename exists and is a symlink (not a hard link),
     *   false otherwise.
     */
    public function isSymlink(PathInterface $path): bool;

    /**
     * Determines whether the given path is writable.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     */
    public function isWritable(PathInterface $path): bool;

    /**
     * Recursively creates a directory at the given path.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   The directory to create.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the directory cannot be created.
     */
    public function mkdir(PathInterface $path): void;

    /**
     * Returns the target of a symbolic link.
     *
     * Hard links are not included and will throw an exception. Consider using
     * ::isSymlink() first.
     *
     * Note: PHP does not distinguish between absolute and relative links on
     * Windows, so the returned path object there will be based on a canonicalized,
     * absolute path string. In other words, ALL link paths on Windows will
     * behave like absolute links, whether they really are or not.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   The link path.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the path is not a symbolic link (symlink) or cannot be read. Hard
     *   links are distinct from symlinks and will still throw an exception.
     */
    public function readLink(PathInterface $path): PathInterface;

    /**
     * Removes the given path.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to remove.
     * @param \PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int $timeout
     *   An optional process timeout (maximum runtime) in seconds. If set to
     *   zero (0), no time limit is imposed.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the file cannot be removed.
     */
    public function remove(
        PathInterface $path,
        ?OutputCallbackInterface $callback = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void;
}
