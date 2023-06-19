<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Core;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;

/**
 * @package Core
 *
 * @api This class is subject to our backward compatibility promise and may be safely depended upon.
 */
final class Beginner implements BeginnerInterface
{
    public function __construct(
        private readonly FileSyncerInterface $fileSyncer,
        private readonly BeginnerPreconditionsInterface $preconditions,
    ) {
    }

    public function begin(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir, $exclusions);

        try {
            $this->fileSyncer->sync($activeDir, $stagingDir, $exclusions, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getTranslatableMessage(), 0, $e);
        }
    }
}
