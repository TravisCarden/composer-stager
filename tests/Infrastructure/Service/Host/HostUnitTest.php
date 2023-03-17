<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Host;

use PhpTuf\ComposerStager\Infrastructure\Service\Host\Host;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host */
final class HostUnitTest extends TestCase
{
    protected function createSut(): Host
    {
        return new Host();
    }

    /** @covers ::isWindows */
    public function testWindows(): void
    {
        $isWindowsDirectorySeparator = DIRECTORY_SEPARATOR === '\\';
        $sut = $this->createSut();

        self::assertEquals($isWindowsDirectorySeparator, $sut->isWindows());
    }
}
