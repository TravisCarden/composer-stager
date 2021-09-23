<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Requires throwable classes to implement ExceptionInterface.
 */
class MissingExceptionInterfaceRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if (!$this->isThrowable($class)) {
            return[];
        }

        if (!array_key_exists(ExceptionInterface::class, $class->getInterfaces())) {
            $message = sprintf('Throwable class must implement %s', ExceptionInterface::class);
            return [RuleErrorBuilder::message($message)->build()];
        }

        return [];
    }
}
