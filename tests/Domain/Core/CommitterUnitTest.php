<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Domain\Core;

use PhpTuf\ComposerStager\Domain\Core\Committer;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommitterPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Tests\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Core\Committer
 *
 * @covers \PhpTuf\ComposerStager\Domain\Core\Committer::__construct
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 *
 * @property \PhpTuf\ComposerStager\Domain\FileSyncer\Service\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy $fileSyncer
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\CommitterPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $preconditions
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $activeDir
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $stagingDir
 */
final class CommitterUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = new TestPath(self::ACTIVE_DIR);
        $this->stagingDir = new TestPath(self::STAGING_DIR);
        $this->preconditions = $this->prophesize(CommitterPreconditionsInterface::class);
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
    }

    private function createSut(): Committer
    {
        $preconditions = $this->preconditions->reveal();
        $fileSyncer = $this->fileSyncer->reveal();

        return new Committer($fileSyncer, $preconditions);
    }

    /** @covers ::commit */
    public function testCommitWithMinimumParams(): void
    {
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, null)
            ->shouldBeCalledOnce();
        $this->fileSyncer
            ->sync($this->stagingDir, $this->activeDir, null, null, ProcessRunnerInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit($this->stagingDir, $this->activeDir);
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerCommitWithOptionalParams
     */
    public function testCommitWithOptionalParams(
        string $stagingDir,
        string $activeDir,
        ?PathListInterface $exclusions,
        ?ProcessOutputCallbackInterface $callback,
        ?int $timeout,
    ): void {
        $activeDir = new TestPath($activeDir);
        $stagingDir = new TestPath($stagingDir);
        $this->preconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledOnce();
        $this->fileSyncer
            ->sync($stagingDir, $activeDir, $exclusions, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit($stagingDir, $activeDir, $exclusions, $callback, $timeout);
    }

    public function providerCommitWithOptionalParams(): array
    {
        return [
            [
                'stagingDir' => '/one/two',
                'activeDir' => '/three/four',
                'exclusions' => null,
                'callback' => null,
                'timeout' => null,
            ],
            [
                'stagingDir' => 'five/six',
                'activeDir' => 'seven/eight',
                'exclusions' => new TestPathList(),
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /** @covers ::commit */
    public function testCommitPreconditionsUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->commit($this->stagingDir, $this->activeDir);
        }, PreconditionException::class, $message);
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerExceptions
     */
    public function testExceptions(ExceptionInterface $exception, string $message): void
    {
        $this->fileSyncer
            ->sync($this->stagingDir, $this->activeDir, Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->commit($this->stagingDir, $this->activeDir);
        }, RuntimeException::class, $message, $exception::class);
    }

    public function providerExceptions(): array
    {
        return [
            [
                'exception' => new InvalidArgumentException(new TestTranslatableMessage('one')),
                'message' => 'one',
            ],
            [
                'exception' => new IOException(new TestTranslatableMessage('two')),
                'message' => 'two',
            ],
        ];
    }
}
