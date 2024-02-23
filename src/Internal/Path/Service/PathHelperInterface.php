<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Path\Service;

/**
 * @package Path
 *
 * @internal Don't depend directly on this interface. It may be changed or removed at any time without notice.
 */
interface PathHelperInterface
{
    /** Canonicalizes the given path. */
    public function canonicalize(string $path): string;

    /** Determines whether the given path is absolute. */
    public function isAbsolute(string $path): bool;

    /** Determines whether the given path is relative. */
    public function isRelative(string $path): bool;

    /**
     * Makes one path relative to another.
     *
     * For example, given a path of "/var/one" and a base path of "/var/two",
     * it would return "../one".
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\InvalidArgumentException
     *   If the path cannot be made relative because the base path is relative
     *   or because they have different roots.
     */
    public function makeRelative(string $path, string $basePath): string;
}
