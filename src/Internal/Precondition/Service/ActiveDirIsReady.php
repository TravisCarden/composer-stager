<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend on this class. It may be changed or removed at any time without notice.
 */
final class ActiveDirIsReady extends AbstractPreconditionsTree implements ActiveDirIsReadyInterface
{
    public function __construct(
        ActiveDirExistsInterface $activeDirExists,
        ActiveDirIsWritableInterface $activeDirIsWritable,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($translatableFactory, $activeDirExists, $activeDirIsWritable);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Active directory is ready');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The preconditions for using the active directory.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The active directory is ready to use.');
    }
}
