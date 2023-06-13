<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core;

use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\CommitterPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;

/**
 * @package Core
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class Committer implements CommitterInterface
{
    public function __construct(
        private readonly FileSyncerInterface $fileSyncer,
        private readonly CommitterPreconditionsInterface $preconditions,
    ) {
    }

    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir, $exclusions);

        try {
            $this->fileSyncer->sync($stagingDir, $activeDir, $exclusions, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getTranslatableMessage(), 0, $e);
        }
    }
}
