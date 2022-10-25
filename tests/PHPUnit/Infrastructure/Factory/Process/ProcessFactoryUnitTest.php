<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Factory\Process;

use PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Symfony\Component\Process\Process;

/** @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory */
final class ProcessFactoryUnitTest extends TestCase
{
    /**
     * @covers ::create
     *
     * @dataProvider providerFactory
     */
    public function testFactory(array $command): void
    {
        $sut = new ProcessFactory();

        $actual = $sut->create($command);

        $expected = new Process($command);
        self::assertEquals($expected, $actual);
    }

    public function providerFactory(): array
    {
        return [
            [[]],
            [['one']],
            [['one', 'two']],
        ];
    }
}
