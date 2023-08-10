<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Value\Path
 *
 * @covers ::__construct
 * @covers ::absolute
 * @covers ::doAbsolute
 * @covers ::isAbsolute
 * @covers ::normalize
 * @covers ::relative
 * @covers \PhpTuf\ComposerStager\Internal\Path\Value\Path::getcwd
 *
 * @group no_windows
 */
final class UnixLikePathUnitTest extends PathUnitTestCase
{
    public function providerBasicFunctionality(): array
    {
        return [
            // Special base paths.
            'Path as empty string ()' => [
                'given' => '',
                'baseDir' => '/var/one',
                'isAbsolute' => false,
                'absolute' => '/var/one',
                'relativeBase' => '/tmp/two',
                'relative' => '/tmp/two',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'baseDir' => '/var/three',
                'isAbsolute' => false,
                'absolute' => '/var/three',
                'relativeBase' => '/tmp/four',
                'relative' => '/tmp/four',
            ],
            'Path as dot-slash (./)' => [
                'given' => './',
                'baseDir' => '/var/five',
                'isAbsolute' => false,
                'absolute' => '/var/five',
                'relativeBase' => '/tmp/six',
                'relative' => '/tmp/six',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'one',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'baseDir' => '/var/two',
                'isAbsolute' => false,
                'absolute' => '/var/two/ ',
                'relativeBase' => '/tmp/three',
                'relative' => '/tmp/three/ ',
            ],
            'Relative path with depth' => [
                'given' => 'one/two/three/four/five',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one/two/three/four/five',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one/two/three/four/five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'one/two/',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one/two',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one/two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'one//two////three',
                'baseDir' => '/var/four',
                'isAbsolute' => false,
                'absolute' => '/var/four/one/two/three',
                'relativeBase' => '/tmp/five',
                'relative' => '/tmp/five/one/two/three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '../one/../two/three/four/../../five/six/..',
                'baseDir' => '/var/seven/eight',
                'isAbsolute' => false,
                'absolute' => '/var/seven/two/five',
                'relativeBase' => '/tmp/nine/ten',
                'relative' => '/tmp/nine/two/five',
            ],
            'Relative path with leading double dots (..) and root base path' => [
                'given' => '../one/two',
                'baseDir' => '/',
                'isAbsolute' => false,
                'absolute' => '/one/two',
                'relativeBase' => '/three/..',
                'relative' => '/one/two',
            ],
            'Silly combination of relative path as double dots (..) with root base path' => [
                'given' => '..',
                'baseDir' => '/',
                'isAbsolute' => false,
                'absolute' => '/',
                'relativeBase' => '/',
                'relative' => '/',
            ],
            'Crazy relative path' => [
                'given' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'baseDir' => '/seven/eight/nine/ten',
                'isAbsolute' => false,
                'absolute' => '/seven/eight/nine/ten/one/six',
                'relativeBase' => '/eleven/twelve/thirteen/fourteen',
                'relative' => '/eleven/twelve/thirteen/fourteen/one/six',
            ],
            // Absolute paths.
            'Absolute path to the root' => [
                'given' => '/',
                'baseDir' => '/',
                'isAbsolute' => true,
                'absolute' => '/',
                'relativeBase' => '/',
                'relative' => '/',
            ],
            'Absolute path as simple string' => [
                'given' => '/one',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => '/one',
                'relativeBase' => '/tmp',
                'relative' => '/one',
            ],
            'Absolute path with depth' => [
                'given' => '/one/two/three/four/five',
                'baseDir' => '/var/six/seven/eight/nine',
                'isAbsolute' => true,
                'absolute' => '/one/two/three/four/five',
                'relativeBase' => '/tmp/ten/eleven/twelve/thirteen',
                'relative' => '/one/two/three/four/five',
            ],
            'Crazy absolute path' => [
                'given' => '/one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'baseDir' => '/var/seven/eight/nine',
                'isAbsolute' => true,
                'absolute' => '/one/six',
                'relativeBase' => '/tmp/ten/eleven/twelve',
                'relative' => '/one/six',
            ],
        ];
    }

    public function providerBaseDirArgument(): array
    {
        return [
            'With $basePath argument.' => [
                'path' => 'one',
                'baseDir' => new TestPath('/arg'),
                'absolute' => '/arg/one',
            ],
            'With explicit null $basePath argument' => [
                'path' => 'one',
                'baseDir' => null,
                'absolute' => sprintf('%s/one', getcwd()),
            ],
        ];
    }
}
