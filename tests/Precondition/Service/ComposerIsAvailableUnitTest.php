<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable;
use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslationParameters;
use Prophecy\Argument;
use Symfony\Component\Process\Exception\LogicException as SymfonyLogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable
 *
 * @covers ::__construct
 * @covers ::assertExecutableExists
 * @covers ::assertIsActuallyComposer
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getProcess
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 * @covers ::isValidExecutable
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 *
 * @property \PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinderInterface|\Prophecy\Prophecy\ObjectProphecy $executableFinder
 * @property \PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $processFactory
 * @property \Symfony\Component\Process\Process|\Prophecy\Prophecy\ObjectProphecy $process
 */
final class ComposerIsAvailableUnitTest extends PreconditionTestCase
{
    private const COMPOSER_PATH = '/usr/bin/composer';

    protected function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find('composer')
            ->willReturn(self::COMPOSER_PATH);
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->process
            ->mustRun()
            ->willReturn($this->process);
        $this->process
            ->getOutput()
            ->willReturn(json_encode([
                'application' => [
                    'name' => 'Composer',
                    'version' => '2.0.0',
                ],
            ], JSON_THROW_ON_ERROR));

        parent::setUp();
    }

    protected function createSut(): ComposerIsAvailable
    {
        $executableFinder = $this->executableFinder->reveal();
        $process = $this->process->reveal();
        $this->processFactory
            ->create(Argument::cetera())
            ->willReturn($process);
        $processFactory = $this->processFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new ComposerIsAvailable($executableFinder, $processFactory, $translatableFactory);
    }

    /**
     * @covers ::assertExecutableExists
     * @covers ::assertIsActuallyComposer
     * @covers ::getProcess
     * @covers ::isValidExecutable
     */
    public function testFulfilled(): void
    {
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->process
            ->mustRun()
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->processFactory
            ->create([
                self::COMPOSER_PATH,
                'list',
                '--format=json',
            ])
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn($this->process->reveal());

        $this->doTestFulfilled('Composer is available.');
    }

    /** @covers ::assertExecutableExists */
    public function testExecutableNotFound(): void
    {
        $previous = LogicException::class;
        $this->executableFinder
            ->find('composer')
            ->willThrow($previous);
        $sut = $this->createSut();

        $message = 'Cannot find Composer.';
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($this->activeDir, $this->stagingDir, $this->exclusions),
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        }, PreconditionException::class, $message, $previous);
    }

    /**
     * @covers ::assertExecutableExists
     * @covers ::assertIsActuallyComposer
     * @covers ::assertIsFulfilled
     * @covers ::getProcess
     */
    public function testFailedToCreateProcess(): void
    {
        $message = __METHOD__;
        $previous = new LogicException(new TestTranslatableMessage($message));
        $this->processFactory
            ->create(Argument::type('array'))
            ->willThrow($previous);

        $this->doTestUnfulfilled(new TestTranslatableExceptionMessage(
            'Cannot check for Composer due to a host configuration problem: %details',
            new TestTranslationParameters(['%details' => $message]),
        ), $previous::class);
    }

    /** @covers ::getProcess */
    public function testFailedToRunProcess(): void
    {
        $this->process
            ->mustRun()
            ->willThrow(ProcessFailedException::class);
        $sut = $this->createSut();

        $message = $this->invalidComposerErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($this->activeDir, $this->stagingDir, $this->exclusions),
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        }, PreconditionException::class, $message);
    }

    public function testFailedToGetOutput(): void
    {
        $previous = SymfonyLogicException::class;
        $this->process
            ->getOutput()
            ->willThrow($previous);
        $sut = $this->createSut();

        $message = $this->invalidComposerErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($this->activeDir, $this->stagingDir, $this->exclusions),
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        }, PreconditionException::class, $message);
    }

    /** @dataProvider providerInvalidOutput */
    public function testInvalidOutput(string $output): void
    {
        $this->process
            ->getOutput()
            ->willReturn($output);
        $sut = $this->createSut();

        $message = $this->invalidComposerErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($this->activeDir, $this->stagingDir, $this->exclusions),
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        }, PreconditionException::class, $message);
    }

    public function providerInvalidOutput(): array
    {
        return [
            'No output' => [''],
            'Empty JSON object' => ['{}'],
            'Missing application name' => [
                json_encode((object) [
                    'application' => [],
                ], JSON_THROW_ON_ERROR),
            ],
            'Incorrect application name' => [
                json_encode((object) [
                    'application' => ['name' => 'Incorrect'],
                ], JSON_THROW_ON_ERROR),
            ],
        ];
    }

    private function invalidComposerErrorMessage(): string
    {
        return sprintf('The Composer executable at %s is invalid.', self::COMPOSER_PATH);
    }
}
