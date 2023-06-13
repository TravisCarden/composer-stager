<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

final class TestPreconditionsTree extends AbstractPreconditionsTree
{
    protected function t(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        return new TestTranslatableMessage($message);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Test preconditions tree');
    }

    public function __construct(PreconditionInterface ...$children)
    {
        $translatableFactory = new TestTranslatableFactory();

        parent::__construct($translatableFactory, ...$children);
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('A generic preconditions tree for automated tests.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('TestPreconditionsTree is unfulfilled.');
    }
}
