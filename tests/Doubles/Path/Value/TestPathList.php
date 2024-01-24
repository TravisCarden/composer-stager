<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Doubles\Path\Value;

use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;

final class TestPathList implements PathListInterface
{
    public function getAll(): array
    {
        return [];
    }

    public function add(...$paths): void
    {
        // Unimplemented.
    }
}
