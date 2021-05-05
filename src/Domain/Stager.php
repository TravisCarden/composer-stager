<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Process\ProcessFactory;
use Symfony\Component\Process\ExecutableFinder;

class Stager
{
    /**
     * @var string[]
     */
    private $composerCommand;

    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $executableFinder;

    /**
     * @var \PhpTuf\ComposerStager\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var \PhpTuf\ComposerStager\Process\ProcessFactory
     */
    private $processFactory;

    /**
     * @var string
     */
    private $stagingDir;

    public function __construct(
        ExecutableFinder $executableFinder,
        Filesystem $filesystem,
        ProcessFactory $processFactory
    ) {
        $this->executableFinder = $executableFinder;
        $this->filesystem = $filesystem;
        $this->processFactory = $processFactory;
    }

    /**
     * @param string[] $composerCommand
     *   The Composer command parts exactly as they would be typed in the
     *   terminal. There's no need to escape them in any way, only to separate
     *   them. Example:
     *
     *   @code{.php}
     *   $command = [
     *     // "composer" is implied.
     *     'require',
     *     'lorem/ipsum:"^1 || ^2"',
     *     '--with-all-dependencies',
     *   ];
     *   @endcode
     *
     *   @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     *
     * @param callable|null $callback A PHP callback to run whenever there is
     *   some output available on STDOUT or STDERR.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function stage(array $composerCommand, string $stagingDir, callable $callback = null): void
    {
        $this->composerCommand = $composerCommand;
        $this->stagingDir = $stagingDir;
        $this->validate();
        $this->runCommand($callback);
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function validate(): void
    {
        $this->validateCommand();
        $this->validatePreconditions();
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function validateCommand(): void
    {
        if ($this->composerCommand === []) {
            throw new LogicException('The command cannot be empty.');
        }
        if (array_key_exists('--working-dir', $this->composerCommand)
            || array_key_exists('-d', $this->composerCommand)) {
            throw new InvalidArgumentException('Cannot use the "--working-dir" (or "-d") option');
        }
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     */
    private function validatePreconditions(): void
    {
        if (!$this->filesystem->exists($this->stagingDir)) {
            throw new DirectoryNotFoundException($this->stagingDir, 'The staging directory does not exist at "%s"');
        }
        if (!$this->filesystem->isWritable($this->stagingDir)) {
            throw new DirectoryNotWritableException($this->stagingDir, 'The staging directory is not writable at "%s"');
        }
    }

    private function runCommand(?callable $callback): void
    {
        /** @var string $composer */
        $composer = $this->executableFinder->find('composer');
        $process = $this->processFactory
            ->create(array_merge([
                $composer,
                "--working-dir={$this->stagingDir}",
            ], $this->composerCommand));
        try {
            $process->mustRun($callback);
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            throw new ProcessFailedException($e->getMessage(), 0, $e);
        }
    }
}
