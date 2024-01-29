<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Finder\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder
 *
 * @covers ::__construct
 */
final class ExecutableFinderFunctionalTest extends TestCase
{
    private function createSut(): ExecutableFinder
    {
        return ContainerTestHelper::get(ExecutableFinder::class);
    }

    /** @covers ::find */
    public function testFindFound(): void
    {
        $sut = $this->createSut();

        $actual = $sut->find('rsync');

        self::assertMatchesRegularExpression('/rsync(.exe)?$/i', $actual);
    }

    /** @covers ::find */
    public function testFindNotFound(): void
    {
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->find('xyz');
        }, LogicException::class);
    }

    /**
     * @covers ::exists
     *
     * @dataProvider providerExists
     */
    public function testExists(string $commandName, bool $expected): void
    {
        $sut = $this->createSut();

        $actual = $sut->exists($commandName);

        self::assertSame($expected, $actual);
    }

    public function providerExists(): array
    {
        return [
            'Exists' => [
                'commandName' => 'composer',
                'expected' => true,
            ],
            'Does not exist' => [
                'commandName' => 'invalid_command',
                'expected' => false,
            ],
        ];
    }
}
