<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Finder;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @package Finder
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class ExecutableFinder implements ExecutableFinderInterface
{
    use TranslatableAwareTrait;

    public function __construct(
        private readonly SymfonyExecutableFinder $symfonyExecutableFinder,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function find(string $name): string
    {
        // Look for executable.
        $this->symfonyExecutableFinder->addSuffix('.phar');
        $path = $this->symfonyExecutableFinder->find($name);

        // Cache and throw exception if not found.
        if ($path === null) {
            throw new LogicException($this->t(
                'The %name executable cannot be found. Make sure it\'s installed and in the $PATH',
                $this->p(['%name' => $name]),
            ));
        }

        return $path;
    }
}
