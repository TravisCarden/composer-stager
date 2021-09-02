<?php

namespace PhpTuf\ComposerStager\Tests\Unit;

use Prophecy\PhpUnit\ProphecyTrait;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    protected const ACTIVE_DIR_DEFAULT = '/var/www/active';
    protected const STAGING_DIR_DEFAULT = '/var/www/staging';

    /**
     * Makes paths portable by ensuring directory separators match the OS.
     *
     * @param ...$paths
     *   An arbitrary number of paths as strings.
     */
    protected function fixSeparatorsByReference(&...$paths): void
    {
        foreach ($paths as &$path) {
            $path = $this->fixSeparators($path);
        }
    }

    protected function fixSeparators($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
