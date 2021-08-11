<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use Symfony\Component\Process\Process;

/**
 * Creates Symfony Process objects.
 */
interface ProcessFactoryInterface
{
    /**
     * Creates a process object.
     *
     * @param string[] $command
     *   The command to run and its arguments listed as separate entries. Example:
     *   ```php
     *   $command = [
     *       'composer',
     *       'require',
     *       'lorem/ipsum:"^1 || ^2"',
     *       '--with-all-dependencies',
     *   ];
     *   ```
     * @see \Symfony\Component\Process\Process::__construct
     *
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     *   If the process cannot be created.
     */
    public function create(array $command): Process;
}
