<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Factory;

use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Symfony\Component\Process\Process;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactory */
final class SymfonyProcessFactoryUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::create
     *
     * @dataProvider providerFactory
     */
    public function testFactory(array $command): void
    {
        $translatableFactory = new TestTranslatableFactory();
        $sut = new SymfonyProcessFactory($translatableFactory);

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