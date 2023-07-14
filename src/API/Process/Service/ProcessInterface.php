<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Process\Service;

/**
 * Builds and runs shell commands.
 *
 * @package Process
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface ProcessInterface
{
    /** The default process timeout. */
    public const DEFAULT_TIMEOUT = 120;

    /**
     * Returns the current output of the process (STDOUT).
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the process is not started.
     */
    public function getOutput(): string;

    /**
     * Runs the process.
     *
     * This is identical to run() except that an exception is thrown if the process exits with a non-zero exit code.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\RuntimeException
     *   If the process doesn't terminate successfully.
     */
    public function mustRun(?OutputCallbackInterface $callback = null): self;

    /**
     * Runs the process.
     *
     * @return int
     *   The exit status code: 0 for success or non-zero for any error condition.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\RuntimeException
     *   If the process fails to run for any reason.
     */
    public function run(?OutputCallbackInterface $callback = null): int;

    /**
     * Sets the process timeout (max. runtime) in seconds.
     *
     * @param float|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\InvalidArgumentException
     *   If the given timeout is negative.
     */
    public function setTimeout(?float $timeout = self::DEFAULT_TIMEOUT): self;
}
