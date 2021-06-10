<?php

namespace PhpTuf\ComposerStager\Exception;

use Throwable;

class DirectoryAlreadyExistsException extends PathException
{
    public function __construct(
        string $path,
        string $message = 'Directory already exists: "%s"',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($path, $message, $code, $previous);
    }
}
