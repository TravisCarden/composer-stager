<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Core;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\ComposerProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;

/**
 * @package Core
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class Stager implements StagerInterface
{
    use TranslatableAwareTrait;

    public function __construct(
        private readonly ComposerProcessRunnerInterface $composerRunner,
        private readonly StagerPreconditionsInterface $preconditions,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function stage(
        array $composerCommand,
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir);

        $this->validateCommand($composerCommand);

        $this->runCommand($stagingDir, $composerCommand, $callback, $timeout);
    }

    /**
     * @param array<string> $composerCommand
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\InvalidArgumentException
     */
    private function validateCommand(array $composerCommand): void
    {
        if ($composerCommand === []) {
            throw new InvalidArgumentException($this->t('The Composer command cannot be empty'));
        }

        if (reset($composerCommand) === 'composer') {
            throw new InvalidArgumentException(
                $this->t('The Composer command cannot begin with "composer"--it is implied'),
            );
        }

        if (array_key_exists('--working-dir', $composerCommand) || array_key_exists('-d', $composerCommand)) {
            throw new InvalidArgumentException(
                $this->t('Cannot stage a Composer command containing the "--working-dir" (or "-d") option'),
            );
        }
    }

    /**
     * @param array<string> $composerCommand
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\RuntimeException
     */
    private function runCommand(
        PathInterface $stagingDir,
        array $composerCommand,
        ?ProcessOutputCallbackInterface $callback,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        $command = array_merge(
            ['--working-dir=' . $stagingDir->resolved()],
            $composerCommand,
        );

        try {
            $this->composerRunner->run($command, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getTranslatableMessage(), 0, $e);
        }
    }
}
