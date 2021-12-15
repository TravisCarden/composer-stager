<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\IOException;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

final class ExecutableFinder implements ExecutableFinderInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Exception\IOException[]|string[]|null[]
     */
    private $cache = [];

    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $symfonyExecutableFinder;

    public function __construct(SymfonyExecutableFinder $symfonyExecutableFinder)
    {
        $this->symfonyExecutableFinder = $symfonyExecutableFinder;
    }

    public function find(string $name): string
    {
        $cache = $this->getCache($name);

        // Throw cached exception.
        if ($cache instanceof IOException) {
            throw $cache;
        }

        // Return cached path.
        if ($cache !== null) {
            return $cache;
        }

        // Look for executable.
        $this->symfonyExecutableFinder->addSuffix('.phar');
        $path = $this->symfonyExecutableFinder->find($name);

        // Cache and throw exception if not found.
        if (is_null($path)) {
            $cache = new IOException(
                sprintf('The "%s" executable cannot be found. Make sure it\'s installed and in the $PATH.', $name)
            );
            $this->setCache($name, $cache);
            throw $cache;
        }

        // Cache and return path if found.
        $this->setCache($name, $path);
        return $path;
    }

    /**
     * @param string $commandName
     *
     * @return \PhpTuf\ComposerStager\Exception\IOException|string|null
     */
    private function getCache(string $commandName)
    {
        return $this->cache[$commandName] ?? null;
    }

    /**
     * @param string $commandName
     * @param string|\PhpTuf\ComposerStager\Exception\IOException $value
     */
    private function setCache(string $commandName, $value): void
    {
        $this->cache[$commandName] = $value;
    }
}
