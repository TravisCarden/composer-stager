<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Value\Translation;

use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;

final class TestTranslationParameters implements TranslationParametersInterface
{
    public function __construct(private readonly array $parameters = [])
    {
    }

    public function getAll(): array
    {
        return $this->parameters;
    }
}
