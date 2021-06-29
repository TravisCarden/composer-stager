<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactoryInterface;
use Symfony\Component\Process\Exception\ExceptionInterface as SymfonyExceptionInterface;

/**
 * Provides a base for process runners for consistent process creation and
 * exception-handling.
 *
 * @internal
 */
abstract class AbstractRunner
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinderInterface
     */
    private $executableFinder;

    /**
     * Returns the executable name, e.g., "composer" or "rsync".
     */
    abstract protected function executableName(): string;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactoryInterface
     */
    private $processFactory;

    public function __construct(ExecutableFinderInterface $executableFinder, ProcessFactoryInterface $processFactory)
    {
        $this->executableFinder = $executableFinder;
        $this->processFactory = $processFactory;
    }

    /**
     * @param string[] $command
     *   The command to run and its arguments as separate string values, e.g.,
     *   ['require', 'lorem/ipsum']. The return value of ::executableName() will
     *   be automatically prepended.
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If the executable cannot be found.
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     *   If the command process cannot be created.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function run(array $command, ?ProcessOutputCallbackInterface $callback = null): void
    {
        array_unshift($command, $this->findExecutable());
        $process = $this->processFactory->create($command);
        try {
            $process->mustRun($callback);
        } catch (SymfonyExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    private function findExecutable(): string
    {
        $name = $this->executableName();
        return $this->executableFinder->find($name);
    }
}
