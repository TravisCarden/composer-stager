<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Factory\Translation;

use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;

/**
 * Provides a convenience method for creating translatable objects.
 *
 * @package Translation
 *
 * @api
 */
trait TranslatableAwareTrait
{
    private ?TranslatableFactoryInterface $translatableFactory = null;

    /**
     * Creates a translatable message.
     *
     * @param string $message
     *   A message containing optional placeholders corresponding to parameters (next). Example:
     *   ```php
     *   $message = 'Email %name at <a href="mailto:%email">%email</a>.';
     *   ```
     * @param \PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface|null $parameters
     *   Parameters for the message.
     * @param string|null $domain
     *   An arbitrary domain for grouping translations, e.g., "app", "admin",
     *   "store", or null to use the default.
     */
    protected function t(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        assert(
            $this->translatableFactory instanceof TranslatableFactoryInterface,
            'The "t()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.',
        );

        return $this->translatableFactory->createTranslatableMessage($message, $parameters, $domain);
    }

    /**
     * Creates a translation parameters object.
     *
     * @param array<string, string> $parameters
     *   An associative array keyed by placeholders with their corresponding substitution
     *   values. Placeholders must be in the form /^%\w+$/, i.e., a leading percent sign (%)
     *   followed by one or more alphanumeric characters and underscores, e.g., "%example".
     *   Values must be strings. Example:
     *   ```php
     *   $parameters = [
     *     '%name' => 'John',
     *     '%email' => 'john@example.com',
     *   ];
     *   ```
     */
    protected function p(array $parameters = []): TranslationParametersInterface
    {
        assert(
            $this->translatableFactory instanceof TranslatableFactoryInterface,
            'The "p()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.',
        );

        return $this->translatableFactory->createTranslationParameters($parameters);
    }

    private function setTranslatableFactory(TranslatableFactoryInterface $translatableFactory): void
    {
        $this->translatableFactory = $translatableFactory;
    }
}
