<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

final class EnvironmentHelper
{
    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}
