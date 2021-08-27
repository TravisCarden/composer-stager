<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\Classes\AbstractRule;

/**
 * Requires non-application/non-utility classes have a corresponding interface.
 */
class MissingInterfaceRule extends AbstractRule
{
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($this->isApplicationClass($class) ||
            $this->isUtilClass($class) ||
            $class->isInterface() ||
            $class->isAbstract() ||
            $this->isThrowable($class)
        ) {
            return [];
        }

        $expectedInterface = $class->getName() . 'Interface';
        if (!array_key_exists($expectedInterface, $class->getInterfaces())) {
            $message = sprintf('Non-application/non-utility class must implement a corresponding interface, i.e., %s', $expectedInterface);
            return [RuleErrorBuilder::message($message)->build()];
        }

        return [];
    }
}
