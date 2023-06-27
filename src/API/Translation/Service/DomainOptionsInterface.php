<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Translation\Service;

/**
 * Provides domain values for the translation system.
 *
 * @package Translation
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface DomainOptionsInterface
{
    /** The default domain. */
    public function default(): string;

    /** The domain for exceptions. */
    public function exceptions(): string;
}
