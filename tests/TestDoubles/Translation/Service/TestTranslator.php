<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Service;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

final class TestTranslator implements TranslatorInterface
{
    use TranslatorTrait {
        trans as symfonyTrans;
    }

    public function trans(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
        ?string $locale = null,
    ): string {
        $parameters = $parameters instanceof TranslationParametersInterface
            ? $parameters->getAll()
            : [];

        return $this->symfonyTrans($message, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return TestLocaleOptions::DEFAULT;
    }
}
