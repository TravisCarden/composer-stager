<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;
use PhpTuf\ComposerStager\Tests\Doubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use PhpTuf\ComposerStager\Tests\TestUtils\EnvironmentHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use ReflectionClass;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Value\Path
 *
 * @covers ::__construct
 * @covers ::absolute
 * @covers ::doAbsolute
 * @covers ::getcwd
 * @covers ::getProtocol
 * @covers ::hasProtocol
 * @covers ::isAbsolute
 * @covers ::relative
 * @covers ::stripProtocol
 */
abstract class PathUnitTestCase extends TestCase
{
    /** @dataProvider providerBasicFunctionality */
    public function testBasicFunctionality(
        string $given,
        string $basePath,
        bool $isAbsolute,
        string $absolute,
        string $relativeBase,
        string $relative,
    ): void {
        // Simply fixing separators on non-Windows systems allows for quick smoke testing on them. They'll
        // still be tested "for real" with actual, unchanged paths on an actual Windows system on CI.
        if (!EnvironmentHelper::isWindows()) {
            PathHelper::fixSeparatorsMultiple($given, $basePath, $absolute, $relativeBase, $relative);
        }

        $equalInstance = new Path($given);
        $unequalInstance = new Path(__DIR__);
        $relativeBase = new Path($relativeBase);
        $sut = new Path($given);

        // Dynamically override basePath.
        $overrideBasePath = static function (PathInterface $pathObject, string $basePathOverride): void {
            $reflection = new ReflectionClass($pathObject);
            $reflection->newInstanceWithoutConstructor();
            $basePathProperty = $reflection->getProperty('basePathAbsolute');
            $basePathProperty->setValue($pathObject, $basePathOverride);
        };
        $overrideBasePath($sut, $basePath);
        $overrideBasePath($equalInstance, $basePath);

        self::assertEquals($isAbsolute, $sut->isAbsolute(), 'Correctly determined whether given path was relative.');
        self::assertEquals($absolute, $sut->absolute(), 'Got absolute path.');
        self::assertEquals($relative, $sut->relative($relativeBase), 'Got absolute path relative to another given path.');
        self::assertEquals($sut, $equalInstance, 'Path value considered equal to another instance with the same input.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');

        // Make sure object is truly immutable.
        chdir(__DIR__);
        self::assertEquals($absolute, $sut->absolute(), 'Retained correct value after changing working directory.');
        self::assertEquals($sut, $equalInstance, 'Path value still considered equal to another instance with the same input after changing working directory.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');
    }

    abstract public function providerBasicFunctionality(): array;

    /** @dataProvider providerBaseDirArgument */
    public function testOptionalBaseDirArgument(string $path, ?PathInterface $basePath, string $absolute): void
    {
        // Simply fixing separators on non-Windows systems allows for quick smoke testing on them. They'll
        // still be tested "for real" with actual, unchanged paths on an actual Windows system on CI.
        if (!EnvironmentHelper::isWindows()) {
            PathHelper::fixSeparatorsMultiple($path, $absolute);
        }

        $sut = new Path($path, $basePath);

        self::assertEquals($absolute, $sut->absolute(), 'Got absolute path.');
    }

    abstract public function providerBaseDirArgument(): array;

    /**
     * @dataProvider providerGetCwd
     *
     * @runInSeparateProcess
     */
    public function testGetCwd(
        string|false $builtInReturn,
        string $md5,
        string $sysGetTempDir,
        string $expectedSutReturn,
    ): void {
        BuiltinFunctionMocker::mock([
            'getcwd' => $this->prophesize(TestSpyInterface::class),
            'md5' => $this->prophesize(TestSpyInterface::class),
            'sys_get_temp_dir' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['getcwd']
            ->report()
            ->shouldBeCalledOnce()
            ->willReturn($builtInReturn);
        BuiltinFunctionMocker::$spies['md5']
            ->report()
            ->willReturn($md5);
        BuiltinFunctionMocker::$spies['sys_get_temp_dir']
            ->report()
            ->willReturn($sysGetTempDir);

        $reflection = new ReflectionClass(Path::class);
        $sut = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('getcwd');
        $actualSutReturn = $method->invoke($sut);

        self::assertSame($expectedSutReturn, $actualSutReturn);
    }

    public function providerGetCwd(): array
    {
        return [
            'Normal return' => [
                'builtInReturn' => __DIR__,
                'md5' => '',
                'sys_get_temp_dir' => '',
                'expectedSutReturn' => __DIR__,
            ],
            'Failure' => [
                'builtInReturn' => false,
                'md5' => '1234',
                'sys_get_temp_dir' => '/temp',
                'expectedSutReturn' => '/temp/composer-stager/error-1234',
            ],
        ];
    }
}
