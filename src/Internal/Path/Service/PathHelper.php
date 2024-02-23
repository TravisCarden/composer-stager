<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Path\Service;

use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException as SymfonyInvalidArgumentException;
use Symfony\Component\Filesystem\Path as SymfonyPath;

/**
 * @package Path
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class PathHelper implements PathHelperInterface
{
    use TranslatableAwareTrait;

    public function __construct(TranslatableFactoryInterface $translatableFactory)
    {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function canonicalize(string $path): string
    {
        $path = SymfonyPath::canonicalize($path);

        // SymfonyPath always uses forward slashes. Use the OS's
        // directory separator instead. And it doesn't reduce repeated
        // slashes after Windows drive names, so eliminate them, too.
        $canonicalized = preg_replace('#/+#', DIRECTORY_SEPARATOR, $path);

        assert(is_string($canonicalized));

        return $canonicalized;
    }

    public function isAbsolute(string $path): bool
    {
        return SymfonyPath::isAbsolute($path);
    }

    public function isRelative(string $path): bool
    {
        return SymfonyPath::isRelative($path);
    }

    public function makeRelative(string $path, string $basePath): string
    {
        try {
            return SymfonyPath::makeRelative($path, $basePath);
        } catch (SymfonyInvalidArgumentException $e) {
            $path = $this->canonicalize($path);
            $basePath = $this->canonicalize($basePath);

            throw new InvalidArgumentException($this->t(
                'The path %path cannot be made relative to %base_path: %details',
                $this->p([
                    '%path' => $path,
                    '%base_path' => $basePath,
                    '%details' => $e->getMessage(),
                ]),
                $this->d()->exceptions(),
            ), 0, $e);
        }
    }
}
