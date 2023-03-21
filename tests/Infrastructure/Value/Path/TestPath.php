<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class TestPath implements PathInterface
{
    public function __construct(private readonly string $path = 'test', private readonly bool $isAbsolute = true)
    {
    }

    public function isAbsolute(): bool
    {
        return $this->isAbsolute;
    }

    public function raw(): string
    {
        return $this->path;
    }

    public function resolved(): string
    {
        return $this->path;
    }

    public function resolvedRelativeTo(PathInterface $path): string
    {
        return $this->path;
    }
}
