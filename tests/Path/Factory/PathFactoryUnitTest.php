<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Host\Service\Host;
use PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Path\Value\UnixLikePath;
use PhpTuf\ComposerStager\Infrastructure\Path\Value\WindowsPath;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\AbstractPath
 */
final class PathFactoryUnitTest extends TestCase
{
    /**
     * It's difficult to meaningfully test this class because it is a static
     * factory, but it has an external dependency on a PHP constant that cannot
     * be mocked. The tests themselves must therefore be conditioned on the
     * external environment, which is obviously "cheating".
     *
     * @covers ::create
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        string $string,
        PathInterface $cwd,
        PathInterface $expected,
        PathInterface $expectedWithCwd,
    ): void {
        $actual = PathFactory::create($string);
        $actualWithCwd = PathFactory::create($string, $cwd);

        self::assertEquals($expected, $actual, 'Returned correct path object.');
        self::assertEquals($expectedWithCwd, $actualWithCwd, 'Returned correct path object given a $cwd argument.');
    }

    public function providerBasicFunctionality(): array
    {
        if (Host::isWindows()) {
            return [
                [
                    'string' => 'test.txt',
                    'cwd' => new TestPath(),
                    'expected' => new WindowsPath('test.txt'),
                    'expectedWithCwd' => new WindowsPath('test.txt', new TestPath()),
                ],
            ];
        }

        return [
            [
                'string' => 'test.txt',
                'cwd' => new TestPath(),
                'expected' => new UnixLikePath('test.txt'),
                'expectedWithCwd' => new UnixLikePath('test.txt', new TestPath()),
            ],
        ];
    }
}
