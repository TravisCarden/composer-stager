<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\RsyncProcessRunnerInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer
 *
 * @covers ::__construct
 * @covers ::buildCommand
 * @covers ::ensureDestinationDirectoryExists
 * @covers ::getRelativePath
 * @covers ::runCommand
 * @covers ::sync
 *
 * @group no_windows
 */
final class RsyncFileSyncerUnitTest extends TestCase
{
    private EnvironmentInterface|ObjectProphecy $environment;
    private FilesystemInterface|ObjectProphecy $filesystem;
    private RsyncProcessRunnerInterface|ObjectProphecy $rsync;

    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->setTimeLimit(Argument::type('integer'))
            ->willReturn(true);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->fileExists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->filesystem
            ->isDir(Argument::any())
            ->willReturn(true);
        $this->rsync = $this->prophesize(RsyncProcessRunnerInterface::class);

        parent::setUp();
    }

    private function createSut(): RsyncFileSyncer
    {
        $environment = $this->environment->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathHelper = PathTestHelper::createPathHelper();
        $pathListFactory = PathTestHelper::createPathListFactory();
        $rsync = $this->rsync->reveal();
        $translatableFactory = TranslationTestHelper::createTranslatableFactory();

        return new RsyncFileSyncer($environment, $filesystem, $pathHelper, $pathListFactory, $rsync, $translatableFactory);
    }

    /**
     * @covers ::sync
     *
     * @dataProvider providerSync
     */
    public function testSync(
        string $source,
        string $destination,
        array $optionalArguments,
        array $expectedCommand,
        ?OutputCallbackInterface $expectedCallback,
    ): void {
        $sourcePath = PathTestHelper::createPath($source);
        $destinationPath = PathTestHelper::createPath($destination);

        $this->filesystem
            ->mkdir($destinationPath)
            ->shouldBeCalledOnce();
        $this->rsync
            ->run($expectedCommand, null, [], $expectedCallback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->sync($sourcePath, $destinationPath, ...$optionalArguments);
    }

    public function providerSync(): array
    {
        return [
            'Minimum arguments' => [
                'source' => '/var/www/source',
                'destination' => '/var/www/destination',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '/var/www/source/',
                    '/var/www/destination',
                ],
                'expectedCallback' => null,
            ],
            'Siblings: no exclusions given' => [
                'source' => '/var/www/source/one',
                'destination' => '/var/www/destination/two',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '/var/www/source/one/',
                    '/var/www/destination/two',
                ],
                'expectedCallback' => null,
            ],
            //'Siblings: simple exclusions given' => [
            'Siblings: simple exclusions given' => [
                'source' => '/var/www/source/two',
                'destination' => '/var/www/destination/two',
                'optionalArguments' => [PathTestHelper::createPathList('three.txt', 'four.txt'), new TestOutputCallback()],
                'expectedCommand' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/three.txt',
                    '--exclude=/four.txt',
                    '/var/www/source/two/',
                    '/var/www/destination/two',
                ],
                'expectedCallback' => new TestOutputCallback(),
            ],
            'Siblings: duplicate exclusions given' => [
                'source' => '/var/www/source/three',
                'destination' => '/var/www/destination/three',
                'optionalArguments' => [
                    PathTestHelper::createPathList('four/five', 'six/seven', 'six/seven', 'six/seven'),
                ],
                'expectedCommand' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/four/five',
                    '--exclude=/six/seven',
                    '/var/www/source/three/',
                    '/var/www/destination/three',
                ],
                'expectedCallback' => null,
            ],
            'Siblings: Windows directory separators' => [
                'source' => '/var/www/source/one\\two',
                'destination' => '/var/www/destination\\one/two',
                'optionalArguments' => [
                    PathTestHelper::createPathList(
                        'three\\four',
                        'five/six/seven/eight',
                        'five/six/seven/eight',
                        'five\\six/seven\\eight',
                        'five/six\\seven/eight',
                    ),
                ],
                'expectedCommand' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/three/four',
                    '--exclude=/five/six/seven/eight',
                    '/var/www/source/one/two/',
                    '/var/www/destination/one/two',
                ],
                'expectedCallback' => null,
            ],
            'Nested: destination inside source (neither is excluded)' => [
                'source' => '/var/www/source',
                'destination' => '/var/www/source/destination',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '/var/www/source/',
                    '/var/www/source/destination',
                ],
                'expectedCallback' => null,
            ],
            'Nested: source inside destination (source is excluded)' => [
                'source' => '/var/www/destination/source',
                'destination' => '/var/www/destination',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    // "Source inside destination" is the only case where the source directory needs to be excluded.
                    '--exclude=/source',
                    '/var/www/destination/source/',
                    '/var/www/destination',
                ],
                'expectedCallback' => null,
            ],
            'Nested: with Windows directory separators' => [
                'source' => '/var/www/destination\\source',
                'destination' => '/var/www/destination',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    // "Source inside destination" is the only case where the source directory needs to be excluded.
                    '--exclude=/source',
                    '/var/www/destination/source/',
                    '/var/www/destination',
                ],
                'expectedCallback' => null,
            ],
        ];
    }

    /**
     * @covers ::runCommand
     *
     * @dataProvider providerSyncFailure
     */
    public function testSyncFailure(ExceptionInterface $caughtException, string $thrownException): void
    {
        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($caughtException);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->sync(PathTestHelper::sourceDirPath(), PathTestHelper::destinationDirPath());
        }, $thrownException, $caughtException->getMessage(), null, $caughtException::class);
    }

    public function providerSyncFailure(): array
    {
        $message = TranslationTestHelper::createTranslatableExceptionMessage(__METHOD__);

        return [
            'LogicException' => [
                'caughtException' => new LogicException($message),
                'thrownException' => IOException::class,
            ],
            'RuntimeException' => [
                'caughtException' => new RuntimeException($message),
                'thrownException' => IOException::class,
            ],
        ];
    }

    /** @covers ::ensureDestinationDirectoryExists */
    public function testSyncCreateDestinationDirectoryFailed(): void
    {
        $message = TranslationTestHelper::createTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->mkdir(PathTestHelper::destinationDirPath())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->sync(PathTestHelper::sourceDirPath(), PathTestHelper::destinationDirPath());
        }, IOException::class, $message);
    }
}
