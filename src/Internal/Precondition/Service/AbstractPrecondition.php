<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @api
 */
abstract class AbstractPrecondition implements PreconditionInterface
{
    use TranslatableAwareTrait;

    final public function getStatusMessage(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): TranslatableInterface {
        try {
            $this->assertIsFulfilled($activeDir, $stagingDir, $exclusions);
        } catch (PreconditionException $e) {
            return $e->getTranslatableMessage();
        }

        return $this->getFulfilledStatusMessage();
    }

    final public function getLeaves(): array
    {
        return [$this];
    }

    final public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): bool {
        try {
            $this->assertIsFulfilled($activeDir, $stagingDir, $exclusions);
        } catch (PreconditionException) {
            return false;
        }

        return true;
    }

    /** Gets a status message for when the precondition is fulfilled. */
    abstract protected function getFulfilledStatusMessage(): TranslatableInterface;

    public function __construct(
        TranslatableFactoryInterface $translatableFactory,
        protected TranslatorInterface $translator,
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }
}
