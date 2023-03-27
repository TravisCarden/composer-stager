<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\CleanerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsReadyInterface;

/** @internal Don't instantiate this class directly. Get it from the service container via its interface. */
final class CleanerPreconditions extends AbstractPreconditionsTree implements CleanerPreconditionsInterface
{
    public function __construct(
        CommonPreconditionsInterface $commonPreconditions,
        StagingDirIsReadyInterface $stagingDirIsReady,
    ) {
        parent::__construct(...func_get_args());
    }

    public function getName(): string
    {
        return 'Cleaner preconditions'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The preconditions for removing the staging directory.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The preconditions for removing the staging directory are fulfilled.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The preconditions for removing the staging directory are unfulfilled.';
    }
}
