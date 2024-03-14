<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable
 *
 * @covers ::__construct
 * @covers ::assertExecutableExists
 * @covers ::assertIsActuallyComposer
 * @covers ::getProcess
 * @covers ::isValidExecutable
 */
final class ComposerIsAvailableUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Composer';
    protected const DESCRIPTION = 'Composer must be available in order to stage commands.';
    protected const FULFILLED_STATUS_MESSAGE = 'Composer is available.';

    private const COMPOSER_PATH = '/usr/bin/composer';

    private ExecutableFinderInterface|ObjectProphecy $executableFinder;
    private ProcessFactoryInterface|ObjectProphecy $processFactory;
    private ProcessInterface|ObjectProphecy $process;

    protected function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find('composer')
            ->willReturn(self::COMPOSER_PATH);
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);
        $this->process = $this->prophesize(ProcessInterface::class);
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
        $environment = $this->environment->reveal();
        $executableFinder = $this->executableFinder->reveal();
        $process = $this->process->reveal();
        $this->processFactory
            ->create(Argument::cetera())
            ->willReturn($process);
        $processFactory = $this->processFactory->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new ComposerIsAvailable($environment, $executableFinder, $processFactory, $translatableFactory);
    }

    /**
     * @covers ::assertExecutableExists
     * @covers ::assertIsActuallyComposer
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
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

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /** @covers ::assertExecutableExists */
    public function testExecutableNotFound(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $previous = LogicException::class;
        $this->executableFinder
            ->find('composer')
            ->willThrow($previous);
        $sut = $this->createSut();

        $message = 'Cannot find Composer.';
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($activeDirPath, $stagingDirPath, $this->exclusions),
        );
        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions);
        }, PreconditionException::class, $message, null, $previous);
    }

    /**
     * @covers ::assertExecutableExists
     * @covers ::assertIsActuallyComposer
     * @covers ::doAssertIsFulfilled
     * @covers ::getProcess
     */
    public function testFailedToCreateProcess(): void
    {
        $message = __METHOD__;
        $previous = new LogicException(self::createTranslatableMessage($message));
        $this->processFactory
            ->create(Argument::type('array'))
            ->willThrow($previous);

        $this->doTestUnfulfilled(sprintf(
            'Cannot check for Composer due to a host configuration problem: %s',
            $message,
        ), $previous::class);
    }

    /** @covers ::getProcess */
    public function testFailedToRunProcess(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->process
            ->mustRun()
            ->willThrow(LogicException::class);
        $sut = $this->createSut();

        $message = $this->invalidComposerErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($activeDirPath, $stagingDirPath, $this->exclusions),
        );
        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions);
        }, PreconditionException::class, $message);
    }

    /** @covers ::isValidExecutable */
    public function testFailedToGetOutput(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $previous = LogicException::class;
        $this->process
            ->getOutput()
            ->willThrow($previous);
        $sut = $this->createSut();

        $message = $this->invalidComposerErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($activeDirPath, $stagingDirPath, $this->exclusions),
        );
        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions);
        }, PreconditionException::class, $message);
    }

    /** @dataProvider providerInvalidOutput */
    public function testInvalidOutput(string $output): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->process
            ->getOutput()
            ->willReturn($output);
        $sut = $this->createSut();

        $message = $this->invalidComposerErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($activeDirPath, $stagingDirPath, $this->exclusions),
        );
        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions);
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
