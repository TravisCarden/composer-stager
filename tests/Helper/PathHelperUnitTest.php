<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Helper;

use PhpTuf\ComposerStager\Internal\Helper\PathHelper;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Helper\PathHelper */
final class PathHelperUnitTest extends TestCase
{
    /**
     * @covers ::canonicalize
     *
     * @dataProvider providerCanonicalize
     */
    public function testCanonicalize(string $unixLike, string $windows, string $expected): void
    {
        $actualUnixLike = PathHelper::canonicalize($unixLike);
        $actualWindows = PathHelper::canonicalize($windows);

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
     *
     * @dataProvider providerIsAbsolute
     */
    public function testIsAbsolute(bool $expected, string $path): void
    {
        self::assertSame($expected, PathHelper::isAbsolute($path));
    }

    public function providerIsAbsolute(): array
    {
        return [
            // Yes.
            [true, '/one/two'],
            [true, 'C:\\One\\Two'],
            [true, '\\One\\Two'],
            // No.
            [false, 'one/two'],
            [false, '../one/two'],
            [false, '..\\One\\Two'],
        ];
    }
}
