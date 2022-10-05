<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Factory\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\Factory\FileSyncer\FileSyncerFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Factory\FileSyncer\FileSyncerFactory
 *
 * @covers ::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy $phpFileSyncer
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy $rsyncFileSyncer
 * @property \Symfony\Component\Process\ExecutableFinder|\Prophecy\Prophecy\ObjectProphecy $executableFinder
 */
final class FileSyncerFactoryUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinder::class);
        $this->executableFinder
            ->find(Argument::any())
            ->willReturn(null);
        $this->phpFileSyncer = $this->prophesize(PhpFileSyncerInterface::class);
        $this->rsyncFileSyncer = $this->prophesize(RsyncFileSyncerInterface::class);
    }

    private function createSut(): FileSyncerFactory
    {
        $executableFinder = $this->executableFinder->reveal();
        $phpFileSyncer = $this->phpFileSyncer->reveal();
        $rsyncFileSyncer = $this->rsyncFileSyncer->reveal();

        return new FileSyncerFactory($executableFinder, $phpFileSyncer, $rsyncFileSyncer);
    }

    /**
     * @covers ::create
     *
     * @dataProvider providerCreate
     */
    public function testCreate(string $executable, int $calledTimes, ?string $path, string $instanceOf): void
    {
        $this->executableFinder
            ->find($executable)
            ->shouldBeCalledTimes($calledTimes)
            ->willReturn($path);
        $sut = $this->createSut();

        $fileSyncer = $sut->create();

        /** @noinspection UnnecessaryAssertionInspection */
        self::assertInstanceOf($instanceOf, $fileSyncer, 'Returned correct file syncer.');
    }

    public function providerCreate(): array
    {
        return [
            [
                'executable' => 'rsync',
                'calledTimes' => 1,
                'path' => '/usr/bin/rsync',
                'instanceOf' => RsyncFileSyncerInterface::class,
            ],
            [
                'executable' => 'n/a',
                'calledTimes' => 0,
                'path' => null,
                'instanceOf' => PhpFileSyncerInterface::class,
            ],
        ];
    }
}
