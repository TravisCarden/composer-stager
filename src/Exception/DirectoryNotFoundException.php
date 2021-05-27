<?php

namespace PhpTuf\ComposerStager\Exception;

use Throwable;

class DirectoryNotFoundException extends PathException implements ExceptionInterface
{
    public function __construct(
        string $path,
        string $message = 'No such directory: "%s"',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($path, $message, $code, $previous);
    }
}
