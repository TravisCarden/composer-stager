<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\AbstractRunner;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\Runner\AbstractRunner
 * @covers \PhpTuf\ComposerStager\Infrastructure\Process\Runner\AbstractRunner::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinderInterface|\Prophecy\Prophecy\ObjectProphecy executableFinder
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactoryInterface|\Prophecy\Prophecy\ObjectProphecy processFactory
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\Process process
 */
class AbstractRunnerTest extends TestCase
{
    private const COMMAND_NAME = 'test';

    public function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find(Argument::any())
            ->willReturnArgument();
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);
        $this->process = $this->prophesize(Process::class);
    }

    private function createSut($executableName = null)
    {
        $executableName = $executableName ?? self::COMMAND_NAME;
        $executableFinder = $this->executableFinder->reveal();
        $process = $this->process->reveal();
        $this->processFactory
            ->create(Argument::cetera())
            ->willReturn($process);
        $processFactory = $this->processFactory->reveal();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($executableName, $executableFinder, $processFactory) extends AbstractRunner
        {
            private $executableName;

            public function __construct($executableName, ExecutableFinderInterface $executableFinder, ProcessFactoryInterface $processFactory)
            {
                parent::__construct($executableFinder, $processFactory);
                $this->executableName = $executableName;
            }

            protected function executableName(): string
            {
                return $this->executableName;
            }
        };
    }

    /**
     * @covers ::executableName
     * @covers ::findExecutable
     * @covers ::run
     *
     * @dataProvider providerRun
     */
    public function testRun($executableName, $givenCommand, $expectedCommand, $callback): void
    {
        $this->executableFinder
            ->find($executableName)
            ->willReturnArgument()
            ->shouldBeCalledOnce();
        $this->process
            ->mustRun($callback)
            ->shouldBeCalledOnce();
        $this->processFactory
            ->create($expectedCommand)
            ->shouldBeCalled()
            ->willReturn($this->process);

        $sut = $this->createSut($executableName);

        $sut->run($givenCommand, $callback);
    }

    public function providerRun(): array
    {
        return [
            [
                'executableName' => 'lorem',
                'givenCommand' => [],
                'expectedCommand' => ['lorem'],
                'callback' => null,
            ],
            [
                'executableName' => 'ipsum',
                'givenCommand' => ['dolor', 'sit'],
                'expectedCommand' => ['ipsum', 'dolor', 'sit'],
                'callback' => null,
            ],
            [
                'executableName' => 'amet',
                'givenCommand' => [],
                'expectedCommand' => ['amet'],
                'callback' => new TestProcessOutputCallback(),
            ],
        ];
    }

    /**
     * @covers ::findExecutable
     * @covers ::run
     */
    public function testRunFailedException(): void
    {
        $this->expectException(ProcessFailedException::class);

        $exception = $this->prophesize(\Symfony\Component\Process\Exception\ProcessFailedException::class);
        $exception = $exception->reveal();
        $this->process
            ->mustRun(Argument::cetera())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->run([static::COMMAND_NAME]);
    }

    /**
     * @covers ::findExecutable
     * @covers ::run
     */
    public function testRunFindExecutableException(): void
    {
        $this->expectException(IOException::class);

        $exception = $this->prophesize(IOException::class);
        $exception = $exception->reveal();
        $this->executableFinder
            ->find(Argument::any())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->run([static::COMMAND_NAME]);
    }
}
