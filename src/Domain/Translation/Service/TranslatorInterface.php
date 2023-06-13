<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Translation\Service;

use PhpTuf\ComposerStager\Domain\Translation\Value\TranslationParametersInterface;

/**
 * Performs message translation.
 *
 * This interface is modeled after the Symfony Translation component. However,
 * there is no guarantee of functional equivalence. Do not depend on undocumented
 * behavior.
 *
 * @see https://symfony.com/doc/current/translation.html
 *
 * @package Translation
 *
 * @api
 */
interface TranslatorInterface
{
    /**
     * Translates the given message.
     *
     * If something goes wrong in production, where `assert()` evaluation is
     * presumably disabled, an error message will instead be returned (not thrown)
     * in order to insulate client code and end users from potentially fatal errors.
     * During development, with `assert()` evaluation enabled, an `AssertError`
     * will be thrown to help surface defects before they're released.
     *
     * @param string $id
     *   The message ID--either the message string itself in the language of the default
     *   locale, or a "keyword" corresponding to the string defined in configuration.
     *   See {@link https://symfony.com/doc/current/translation.html#configuration Configuration
     *   and Basic Translation in the Symfony Docs}. It may contain placeholders corresponding
     *   to the `$parameters` argument.
     * @param \PhpTuf\ComposerStager\Domain\Translation\Value\TranslationParametersInterface|null $parameters
     *   Parameters for the message.
     * @param string|null $domain
     *   An arbitrary domain for grouping translations, e.g., "app", "admin",
     *   "store", or null to use the default.
     * @param string|null $locale
     *   The locale, e.g., "en_US" or "es_ES", or null to use the default.
     */
    public function trans(
        string $id,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
        ?string $locale = null,
    ): string;

    /** Returns the default locale. */
    public function getLocale(): string;
}
