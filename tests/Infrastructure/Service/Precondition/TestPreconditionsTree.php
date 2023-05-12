<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree;

final class TestPreconditionsTree extends AbstractPreconditionsTree
{
    public function getName(): string
    {
        return 'Test preconditions tree';
    }

    public function getDescription(): string
    {
        return 'A generic preconditions tree for automated tests.';
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'TestPreconditionsTree is unfulfilled.';
    }
}
