<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

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

    public function resolve(): string
    {
        return $this->path;
    }

    public function resolveRelativeTo(PathInterface $path): string
    {
        return $this->path;
    }
}
