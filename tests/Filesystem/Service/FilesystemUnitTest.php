<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Tests\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Filesystem\Exception\FileNotFoundException as SymfonyFileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
 *
 * @covers \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem::__construct
 */
final class FilesystemUnitTest extends TestCase
{
    private EnvironmentInterface|ObjectProphecy $environment;
    private PathFactoryInterface|ObjectProphecy $pathFactory;
    private SymfonyFilesystem|ObjectProphecy $symfonyFilesystem;

    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->setTimeLimit(Argument::type('integer'))
            ->willReturn(true);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
        $this->symfonyFilesystem = $this->prophesize(SymfonyFilesystem::class);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): Filesystem
    {
        $environment = $this->environment->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $symfonyFilesystem = $this->symfonyFilesystem->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new Filesystem($environment, $pathFactory, $symfonyFilesystem, $translatableFactory);
    }

    /**
     * @covers ::chmod
     *
     * @group no_windows
     * @runInSeparateProcess
     */
    public function testChmodFailure(): void
    {
        $permissions = 777;
        $pathAbsolute = PathHelper::arbitraryFileAbsolute();
        $this->symfonyFilesystem
            ->exists(Argument::any())
            ->willReturn(true);
        BuiltinFunctionMocker::mock([
            'chmod' => $this->prophesize(TestSpyInterface::class),
            'file_exists' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['chmod']
            ->report($pathAbsolute, $permissions)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        BuiltinFunctionMocker::$spies['file_exists']
            ->report($pathAbsolute)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $message = sprintf('The file mode could not be changed on %s.', $pathAbsolute);
        self::assertTranslatableException(static function () use ($sut, $permissions): void {
            $sut->chmod(PathHelper::arbitraryFilePath(), $permissions);
        }, IOException::class, $message);
    }

    /**
     * @covers ::copy
     *
     * @runInSeparateProcess
     */
    public function testCopySymfonyIOException(): void
    {
        $previousMessage = 'Something went wrong';
        $message = sprintf(
            'Failed to copy %s to %s: %s',
            PathHelper::activeDirAbsolute(),
            PathHelper::stagingDirAbsolute(),
            $previousMessage,
        );
        $previous = new SymfonyIOException($previousMessage);
        $this->symfonyFilesystem
            ->exists(Argument::cetera())
            ->willReturn(true);
        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        BuiltinFunctionMocker::mock(['fileperms' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['fileperms']
            ->report(Argument::cetera())
            ->willReturn(12_345);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->copy(PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, IOException::class, $message, null, $previous::class);
    }

    /**
     * @covers ::copy
     *
     * @runInSeparateProcess
     */
    public function testCopySymfonyFileNotFoundException(): void
    {
        $previousMessage = 'Something went wrong';
        $message = sprintf(
            'The source file does not exist or is not a file at %s: %s',
            PathHelper::activeDirAbsolute(),
            $previousMessage,
        );
        $previous = new SymfonyFileNotFoundException($previousMessage);
        $this->symfonyFilesystem
            ->exists(Argument::cetera())
            ->willReturn(true);
        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        BuiltinFunctionMocker::mock(['fileperms' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['fileperms']
            ->report(Argument::cetera())
            ->willReturn(12_345);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->copy(PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, LogicException::class, $message, null, $previous::class);
    }

    /**
     * @covers ::copy
     *
     * @runInSeparateProcess
     */
    public function testCopy(): void
    {
        $sourceDirPath = PathHelper::sourceDirPath();
        $sourceDirAbsolute = $sourceDirPath->absolute();
        $destinationDirAbsolute = PathHelper::destinationDirAbsolute();
        BuiltinFunctionMocker::mock([
            'chmod' => $this->prophesize(TestSpyInterface::class),
            'fileperms' => $this->prophesize(TestSpyInterface::class),
        ]);
        $this->symfonyFilesystem
            ->copy($sourceDirAbsolute, $destinationDirAbsolute, true)
            ->willReturn(true);
        $this->symfonyFilesystem
            ->exists(Argument::cetera())
            ->willReturn(true);
        BuiltinFunctionMocker::$spies['chmod']
            ->report($destinationDirAbsolute, Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);
        BuiltinFunctionMocker::$spies['fileperms']
            ->report(Argument::cetera())
            ->willReturn(12_345);
        $sut = $this->createSut();

        $sut->copy($sourceDirPath, PathHelper::destinationDirPath());
    }

    /**
     * @covers ::copy
     *
     * @runInSeparateProcess
     */
    public function testCopyChmodFailure(): void
    {
        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->willReturn(true);
        BuiltinFunctionMocker::mock([
            'chmod' => $this->prophesize(TestSpyInterface::class),
            'file_exists' => $this->prophesize(TestSpyInterface::class),
            'fileperms' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['chmod']
            ->report(Argument::cetera())
            ->willReturn(false);
        BuiltinFunctionMocker::$spies['file_exists']
            ->report(Argument::cetera())
            ->willReturn(true);
        BuiltinFunctionMocker::$spies['fileperms']
            ->report(Argument::cetera())
            ->willReturn(12_345);
        $sut = $this->createSut();

        $message = sprintf('The file mode could not be changed on %s.', PathHelper::destinationDirAbsolute());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->copy(PathHelper::activeDirPath(), PathHelper::destinationDirPath());
        }, IOException::class, $message);
    }

    /** @covers ::copy */
    public function testCopyDirectoriesTheSame(): void
    {
        $samePath = PathHelper::activeDirPath();
        $sut = $this->createSut();

        $message = sprintf('The source and destination files cannot be the same at %s', $samePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $samePath): void {
            $sut->copy($samePath, $samePath);
        }, LogicException::class, $message);
    }

    /**
     * @covers ::fileMode
     *
     * @runInSeparateProcess
     */
    public function testFileModeFailure(): void
    {
        $path = PathHelper::arbitraryFilePath();
        FilesystemHelper::touch($path->absolute());
        BuiltinFunctionMocker::mock(['fileperms' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['fileperms']
            ->report($path->absolute())
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $this->symfonyFilesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $sut = $this->createSut();

        $message = sprintf('Failed to get permissions on path at %s', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->fileMode($path);
        }, IOException::class, $message);
    }

    /**
     * @covers ::isWritable
     *
     * @dataProvider providerIsWritable
     *
     * @runInSeparateProcess
     */
    public function testIsWritable(bool $expected): void
    {
        BuiltinFunctionMocker::mock(['is_writable' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['is_writable']
            ->report(PathHelper::arbitraryFileAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->isWritable(PathHelper::arbitraryFilePath());

        self::assertSame($expected, $actual, 'Got correct writable status.');
    }

    public function providerIsWritable(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @covers ::mkdir
     *
     * @runInSeparateProcess
     */
    public function testMkdir(): void
    {
        BuiltinFunctionMocker::mock([
            'is_dir' => $this->prophesize(TestSpyInterface::class),
            'mkdir' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['is_dir']
            ->report(PathHelper::arbitraryDirAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        BuiltinFunctionMocker::$spies['mkdir']
            ->report(PathHelper::arbitraryDirAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->mkdir(PathHelper::arbitraryDirPath());
    }

    /**
     * @covers ::mkdir
     *
     * @runInSeparateProcess
     */
    public function testMkdirFailure(): void
    {
        $dirAbsolute = PathHelper::arbitraryDirAbsolute();
        BuiltinFunctionMocker::mock([
            'is_dir' => $this->prophesize(TestSpyInterface::class),
            'mkdir' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['is_dir']
            ->report($dirAbsolute)
            ->willReturn(false);
        BuiltinFunctionMocker::$spies['mkdir']
            ->report($dirAbsolute)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $message = sprintf('Failed to create directory at %s', $dirAbsolute);
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->mkdir(PathHelper::arbitraryDirPath());
        }, IOException::class, $message);
    }

    /**
     * @covers ::remove
     *
     * @dataProvider providerRemove
     */
    public function testRemove(string $path, ?OutputCallbackInterface $callback, int $timeout): void
    {
        $this->environment->setTimeLimit($timeout)
            ->shouldBeCalledOnce();
        $stagingDir = PathHelper::createPath($path);
        $this->symfonyFilesystem
            ->remove($stagingDir->absolute())
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->remove($stagingDir, $callback, $timeout);
    }

    public function providerRemove(): array
    {
        return [
            'Default values' => [
                'path' => '/one/two',
                'callback' => null,
                'timeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Simple values' => [
                'path' => 'three/four',
                'callback' => new TestOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /** @covers ::remove */
    public function testRemoveException(): void
    {
        $message = 'Failed to remove directory.';
        $previous = new SymfonyIOException($message);
        $this->symfonyFilesystem
            ->remove(Argument::any())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->remove(PathHelper::stagingDirPath());
        }, IOException::class, $message, null, $previous::class);
    }
}
