<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Path\Value;

/**
 * Handles a list of path strings.
 *
 * @package Path
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface PathListInterface
{
    /**
     * Returns all path strings as given, i.e., unresolved.
     *
     * @return array<string>
     */
    public function getAll(): array;

    /**
     * Adds a list of raw path strings.
     *
     * Path strings may be absolute or relative, e.g., "/var/www/example" or
     * "example". Nothing needs to actually exist at them.
     */
    public function add(string ...$paths): void;
}
