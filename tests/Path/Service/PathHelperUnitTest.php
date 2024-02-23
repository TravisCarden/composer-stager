<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Service;

use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelper;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Filesystem\Path as SymfonyPath;
use Throwable;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Service\PathHelper */
final class PathHelperUnitTest extends TestCase
{
    public function createSut(): PathHelper
    {
        $translatableFactory = self::createTranslatableFactory();

        return new PathHelper($translatableFactory);
    }

    /** @covers ::__construct */
    public function testIsTranslatableAware(): void
    {
        $sut = $this->createSut();

        self::assertTranslatableAware($sut);
    }

    /**
     * @covers ::canonicalize
     *
     * @dataProvider providerCanonicalize
     */
    public function testCanonicalize(string $unixLike, string $windows, string $expected): void
    {
        $sut = $this->createSut();

        $actualUnixLike = $sut->canonicalize($unixLike);
        $actualWindows = $sut->canonicalize($windows);

        self::assertSame($expected, $actualUnixLike, 'Correctly canonicalized Unix-like path.');
        self::assertSame($expected, $actualWindows, 'Correctly canonicalized Windows path.');
    }

    public function providerCanonicalize(): array
    {
        return [
            'Empty paths' => [
                'unixLike' => '',
                'windows' => '',
                'expected' => '',
            ],
            'Single dot' => [
                'unixLike' => '.',
                'windows' => '.',
                'expected' => '',
            ],
            'Dot slash' => [
                'unixLike' => './',
                'windows' => '.\\',
                'expected' => '',
            ],
            'Simple path' => [
                'unixLike' => 'one',
                'windows' => 'one',
                'expected' => 'one',
            ],
            'Simple path with depth' => [
                'unixLike' => 'one/two/three/four/five',
                'windows' => 'one\\two\\three\\four\\five',
                'expected' => implode(DIRECTORY_SEPARATOR, ['one', 'two', 'three', 'four', 'five']),
            ],
            'Crazy relative path' => [
                'unixLike' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'windows' => 'one\\.\\\\\\\\.\\two\\three\\four\\five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\six\\\\\\\\\\',
                'expected' => 'one' . DIRECTORY_SEPARATOR . 'six',
            ],
            'Unix-like absolute path' => [
                'unixLike' => '/',
                'windows' => '\\', // This is actually a legitimate UNC path on Windows: @see https://learn.microsoft.com/en-us/dotnet/standard/io/file-path-formats#unc-paths
                'expected' => DIRECTORY_SEPARATOR,
            ],
            'Windows drive name' => [
                'unixLike' => 'C:/', // This would be an absurd Unix-like path, of course, but it's still testable. Same below.
                'windows' => 'C:\\',
                'expected' => 'C:' . DIRECTORY_SEPARATOR,
            ],
            'Windows drive name no slash' => [
                'unixLike' => 'C:',
                'windows' => 'C:',
                'expected' => 'C:' . DIRECTORY_SEPARATOR,
            ],
            'Windows drive name with extra slashes' => [
                'unixLike' => 'C:///',
                'windows' => 'C:\\\\\\',
                'expected' => 'C:' . DIRECTORY_SEPARATOR,
            ],
            'Absolute Windows path with extra slashes' => [
                'unixLike' => 'C:////one',
                'windows' => 'C:\\\\\\\\one',
                'expected' => 'C:' . DIRECTORY_SEPARATOR . 'one',
            ],
        ];
    }

    /**
     * @covers ::isAbsolute
     * @covers ::isRelative
     *
     * @dataProvider providerAbsoluteRelative
     */
    public function testAbsoluteRelative(bool $isAbsolute, string $path): void
    {
        $sut = $this->createSut();

        self::assertSame($isAbsolute, $sut->isAbsolute($path));
        self::assertSame(!$isAbsolute, $sut->isRelative($path));
    }

    public function providerAbsoluteRelative(): array
    {
        return [
            // Yes.
            'True: Unix' => [true, '/one/two'],
            'True: Windows' => [true, 'C:\\One\\Two'],
            'True: UNC' => [true, '\\One\\Two'],
            // No.
            'False: Unix' => [false, 'one/two'],
            'False: Windows' => [false, '../one/two'],
            'False: UNC' => [false, '..\\One\\Two'],
        ];
    }

    /**
     * @covers ::makeRelative
     *
     * @dataProvider providerMakeRelative
     */
    public function testMakeRelative(string $path, string $basePath, string $expected): void
    {
        $sut = $this->createSut();

        self::assertSame($expected, $sut->makeRelative($path, $basePath));
    }

    public function providerMakeRelative(): array
    {
        return [
            'Empty paths' => [
                'path' => '',
                'basePath' => '',
                'expected' => '',
            ],
            'Identical absolute paths' => [
                'path' => '/one/two',
                'basePath' => '/one/two',
                'expected' => '',
            ],
            'Identical relative paths' => [
                'path' => 'one/two',
                'basePath' => 'one/two',
                'expected' => '',
            ],
            'Relative path, absolute base path' => [
                'path' => 'one/two',
                'basePath' => '/three/four',
                'expected' => 'one/two',
            ],
            'Absolute paths with no common ancestor' => [
                'path' => '/one/two/three',
                'basePath' => '/four/five/six',
                'expected' => '../../../one/two/three',
            ],
            'Absolute paths with a common ancestor' => [
                'path' => '/one/two/three',
                'basePath' => '/one/five/six',
                'expected' => '../../two/three',
            ],
            'Crazy paths' => [
                'path' => '/one/.//\\/./two/three/four/five/./././..//.//../\\///../././.././six/\\\\//seven',
                'basePath' => '/one\\.\\\\/\\.\\two\\three\\four\\five\\./.\\.\\..//.\\\\..\\\/\\\\\..\\.\\.\\..\\.\\six/\\\\\\/eight',
                'expected' => '../seven',
            ],
        ];
    }

    /**
     * @covers ::makeRelative
     *
     * @dataProvider providerMakeRelativeException
     */
    public function testMakeRelativeException(string $path, string $basePath, string $expectedExceptionMessage): void
    {
        $sut = $this->createSut();

        $details = '';

        // Get the expected "previous" message from Symfony Path.
        try {
            SymfonyPath::makeRelative($path, $basePath);
        } catch (Throwable $e) {
            $details = $e->getMessage();
        }

        $expectedExceptionMessage = sprintf($expectedExceptionMessage, $details);
        self::assertTranslatableException(static function () use ($sut, $path, $basePath): void {
            $sut->makeRelative($path, $basePath);
        }, InvalidArgumentException::class, $expectedExceptionMessage);
    }

    public function providerMakeRelativeException(): array
    {
        return [
            'Relative base path' => [
                'path' => '/one/two',
                'basePath' => 'three/four',
                'expectedExceptionMessage' => 'The path /one/two cannot be made relative to three/four: %s',
            ],
            'Different roots (Windows)' => [
                'path' => 'C:\\one/two/three',
                'basePath' => 'D:\\four/five/six',
                'expectedExceptionMessage' => 'The path C:/one/two/three cannot be made relative to D:/four/five/six: %s',
            ],
        ];
    }
}
