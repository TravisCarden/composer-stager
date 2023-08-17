<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use AssertionError;
use Closure;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;
use PhpTuf\ComposerStager\Internal\Host\Service\Host;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
use Throwable;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 */
final class PhpFileSyncerUnitTest extends TestCase
{
    private EnvironmentInterface|ObjectProphecy $environment;
    private FileFinderInterface|ObjectProphecy $fileFinder;
    private FilesystemInterface|ObjectProphecy $filesystem;
    private PathFactoryInterface|ObjectProphecy $pathFactory;
    private PathInterface $destination;
    private PathInterface $source;

    protected function setUp(): void
    {
        $this->source = new TestPath(PathHelper::activeDirRelative());
        $this->destination = new TestPath(PathHelper::stagingDirRelative());
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->setTimeLimit(Argument::type('integer'))
            ->willReturn(true);
        $this->fileFinder = $this->prophesize(FileFinderInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->isDirEmpty(Argument::any())
            ->willReturn(false);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
    }

    private function createSut(): PhpFileSyncer
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new PhpFileSyncer($environment, $fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }

    public function testSyncSourceNotFound(): void
    {
        $this->filesystem
            ->exists($this->source)
            ->willReturn(false);
        $sut = $this->createSut();

        $message = sprintf('The source directory does not exist at %s', $this->source->absolute());
        self::assertTranslatableException(function () use ($sut): void {
            $sut->sync($this->source, $this->destination);
        }, LogicException::class, $message);
    }

    public function testSyncDirectoriesTheSame(): void
    {
        $samePath = PathHelper::activeDirPath();
        $sut = $this->createSut();

        $message = sprintf('The source and destination directories cannot be the same at %s', $samePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $samePath): void {
            $sut->sync($samePath, $samePath);
        }, LogicException::class, $message);
    }

    /** @covers ::ensureDestinationExists */
    public function testSyncDestinationCouldNotBeCreated(): void
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->mkdir($this->destination)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut): void {
            $sut->sync($this->source, $this->destination);
        }, $previous::class, $message);
    }

    public function testSyncCouldNotFindDestination(): void
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->fileFinder
            ->find($this->destination, Argument::any())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut): void {
            $sut->sync($this->source, $this->destination);
        }, $previous::class, $message);
    }

    /**
     * @covers ::getRelativePath
     *
     * @dataProvider providerGetRelativePath
     */
    public function testGetRelativePath(string $ancestor, string $path, string $expected): void
    {
        // Expose private method for testing.
        // @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
        // @phpstan-ignore-next-line
        $method = static fn (PhpFileSyncer $sut, $ancestor, $path): string => $sut::getRelativePath($ancestor, $path);
        $sut = $this->createSut();
        $method = Closure::bind($method, null, $sut);

        $actual = $method($sut, $ancestor, $path);

        self::assertEquals($expected, $actual);
    }

    /** @phpcs:disable SlevomatCodingStandard.Whitespaces.DuplicateSpaces.DuplicateSpaces */
    public function providerGetRelativePath(): array
    {
        // UNIX-like OS paths.
        if (!Host::isWindows()) {
            return [
                'Match: single directory depth' => [
                    'ancestor' => 'one',
                    'path'     => 'one/two',
                    'expected' =>     'two',
                ],
                'Match: multiple directories depth' => [
                    'ancestor' => 'one/two',
                    'path'     => 'one/two/three/four/five',
                    'expected' =>         'three/four/five',
                ],
                'No match: no shared ancestor' => [
                    'ancestor' => 'one/two',
                    'path'     => 'three/four/five/six/seven',
                    'expected' => 'three/four/five/six/seven',
                ],
                'No match: identical paths' => [
                    'ancestor' => 'one',
                    'path'     => 'one',
                    'expected' => 'one',
                ],
                'No match: ancestor only as absolute path' => [
                    'ancestor' => '/one/two',
                    'path'     => 'one/two/three/four/five',
                    'expected' => 'one/two/three/four/five',
                ],
                'No match: path only as absolute path' => [
                    'ancestor' => 'one/two',
                    'path'     => '/one/two/three/four/five',
                    'expected' => '/one/two/three/four/five',
                ],
                'No match: sneaky "near match"' => [
                    'ancestor' => 'one',
                    'path'     => 'one_two',
                    'expected' => 'one_two',
                ],
                'Special case: empty strings' => [
                    'ancestor' => '',
                    'path'     => '',
                    'expected' => '',
                ],
            ];
        }

        // Windows paths.
        return [
            'Match: single directory depth' => [
                'ancestor' => 'One',
                'path'     => 'One\\Two',
                'expected' =>      'Two',
            ],
            'Match: multiple directories depth' => [
                'ancestor' => 'One\\Two',
                'path'     => 'One\\Two\\Three\\Four\\Five',
                'expected' =>           'Three\\Four\\Five',
            ],
            'No match: no shared ancestor' => [
                'ancestor' => 'One\\Two',
                'path'     => 'Three\\Four\\Five\\Six\\Seven',
                'expected' => 'Three\\Four\\Five\\Six\\Seven',
            ],
            'No match: identical paths' => [
                'ancestor' => 'One',
                'path'     => 'One',
                'expected' => 'One',
            ],
            'No match: ancestor only as absolute path' => [
                'ancestor' => '\\One\\Two',
                'path'     => 'One\\Two\\Three\\Four\\Five',
                'expected' => 'One\\Two\\Three\\Four\\Five',
            ],
            'No match: path only as absolute path' => [
                'ancestor' => 'One\\Two',
                'path'     => 'C:\\One\\Two\\Three\\Four',
                'expected' => 'C:\\One\\Two\\Three\\Four',
            ],
            'No match: sneaky "near match"' => [
                'ancestor' => 'One',
                'path'     => 'One_Two',
                'expected' => 'One_Two',
            ],
            'Special case: empty strings' => [
                'ancestor' => '',
                'path'     => '',
                'expected' => '',
            ],
        ];
    }

    /**
     * @covers ::assertSourceAndDestinationAreDifferent
     * @covers ::assertSourceExists
     */
    public function testTransMissingTranslatableFactory(): void
    {
        self::assertTranslatableException(function (): void {
            $samePath = PathHelper::activeDirPath();
            $sut = $this->createSut();

            $reflection = new ReflectionClass($sut);
            $sut = $reflection->newInstanceWithoutConstructor();
            $environment = $reflection->getProperty('environment');
            $environment->setValue($sut, $this->environment->reveal());
            $translatableFactory = $reflection->getProperty('translatableFactory');
            $translatableFactory->setValue($sut, null);

            $sut->sync($samePath, $samePath);
        }, AssertionError::class, 'The "p()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.');
    }

    /**
     * @covers ::sync
     *
     * @dataProvider providerTimeout
     */
    public function testTimeout(int $timeout): void
    {
        $this->environment->setTimeLimit($timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        // Use the same path for the source and destination in
        // order to fail validation and avoid side effects.
        try {
            $sut->sync(PathHelper::sourceDirPath(), PathHelper::sourceDirPath(), null, null, $timeout);
        } catch (Throwable) {
            // @ignoreException
        }
    }

    public function providerTimeout(): array
    {
        return [
            [-30],
            [-5],
            [0],
            [5],
            [30],
        ];
    }
}
