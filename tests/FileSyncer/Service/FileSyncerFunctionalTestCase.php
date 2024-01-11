<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

abstract class FileSyncerFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        FilesystemHelper::createDirectories([
            PathHelper::sourceDirAbsolute(),
            PathHelper::destinationDirAbsolute(),
        ]);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    final protected function createSut(): FileSyncerInterface
    {
        return ContainerHelper::get($this->fileSyncerClass());
    }

    abstract protected function fileSyncerClass(): string;

    /**
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::__construct
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceAndDestinationAreDifferent
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceExists
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::sync
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer::copySourceFilesToDestination
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer::deleteExtraneousFilesFromDestination
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer::doSync
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer::doSync
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $source, array $destination, array $expected): void
    {
        self::createFiles(PathHelper::sourceDirAbsolute(), $source);
        self::createFiles(PathHelper::destinationDirAbsolute(), $destination);
        $sut = $this->createSut();

        $sut->sync(PathHelper::sourceDirPath(), PathHelper::destinationDirPath());

        self::assertDirectoryListing(PathHelper::destinationDirAbsolute(), $expected);
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Source and destination both empty' => [
                'source' => [],
                'destination' => [],
                'expected' => [],
            ],
            'Files in source, destination empty' => [
                'source' => ['one.txt', 'two/three.txt'],
                'destination' => [],
                'expected' => ['one.txt', 'two/three.txt'],
            ],
            'Source empty, files in destination' => [
                'source' => [],
                'destination' => ['one.txt', 'two/three.txt'],
                'expected' => [],
            ],
            'Completely different files in source and destination' => [
                'source' => ['one.txt', 'two/three.txt'],
                'destination' => ['four/five.txt', 'five/six.txt'],
                'expected' => ['one.txt', 'two/three.txt'],
            ],
            'Some overlap in files in source and destination' => [
                'source' => ['one.txt', 'two/three.txt'],
                'destination' => ['two/three.txt', 'four/five.txt'],
                'expected' => ['one.txt', 'two/three.txt'],
            ],
        ];
    }

    /** @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceAndDestinationAreDifferent */
    public function testSyncDirectoriesTheSame(): void
    {
        $samePath = PathHelper::arbitraryDirPath();
        $sut = $this->createSut();

        $message = sprintf('The source and destination directories cannot be the same at %s', $samePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $samePath): void {
            $sut->sync($samePath, $samePath);
        }, LogicException::class, $message);
    }

    /**
     * @covers ::sync
     *
     * @dataProvider providerSyncTimeout
     */
    public function testSyncTimeout(int $timeout): void
    {
        $sut = $this->createSut();

        $sut->sync(PathHelper::sourceDirPath(), PathHelper::destinationDirPath(), null, null, $timeout);

        self::assertSame((string) $timeout, ini_get('max_execution_time'), 'Correctly set process timeout.');
    }

    public function providerSyncTimeout(): array
    {
        return [
            'Positive number' => [30],
            'Zero' => [0],
            'Negative number' => [-30],
        ];
    }

    /** @covers ::sync */
    public function testSyncWithDirectorySymlinks(): void
    {
        $link = PathHelper::makeAbsolute('link', PathHelper::sourceDirAbsolute());
        $target = PathHelper::makeAbsolute('directory', PathHelper::sourceDirAbsolute());
        FilesystemHelper::createDirectories($target);
        $file = PathHelper::makeAbsolute('directory/file.txt', PathHelper::sourceDirAbsolute());
        FilesystemHelper::touch($file);
        symlink($target, $link);
        $sut = $this->createSut();

        $sut->sync(PathHelper::sourceDirPath(), PathHelper::destinationDirPath());

        self::assertDirectoryListing(PathHelper::destinationDirAbsolute(), [
            'link',
            'directory/file.txt',
        ], '', 'Correctly synced files, including a symlink to a directory.');
    }

    /** @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceExists */
    public function testSourceDoesNotExist(): void
    {
        $sourcePath = PathHelper::nonExistentDirPath();
        $destinationPath = PathHelper::arbitraryDirPath();

        $sut = $this->createSut();

        $message = sprintf('The source directory does not exist at %s', $sourcePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $sourcePath, $destinationPath): void {
            $sut->sync($sourcePath, $destinationPath);
        }, LogicException::class, $message);
    }
}
