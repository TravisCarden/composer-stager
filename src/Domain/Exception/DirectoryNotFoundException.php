<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use Throwable;

/** This exception is thrown if a directory cannot be found. */
class DirectoryNotFoundException extends PathException
{
    public function __construct(
        string $path,
        string $message = 'No such directory: "%s"',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($path, $message, $code, $previous);
    }
}
