<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\EndToEnd;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer;
use PhpTuf\ComposerStager\Tests\PHPUnit\EndToEnd\EndToEndFunctionalTestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 * @uses  \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner
 * @uses  \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner
 * @uses  \PhpTuf\ComposerStager\Domain\Core\Committer\Committer
 * @uses  \PhpTuf\ComposerStager\Domain\Core\Stager\Stager
 * @uses  \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\FileSyncerFactory
 * @uses  \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer
 * @uses  \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses  \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 */
class PhpFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return PhpFileSyncer::class;
    }
}
