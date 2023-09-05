<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Calls;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use function assert;

/**
 * Forbids instantiating translatables directly.
 *
 * Inspired by https://github.com/BrandEmbassy/phpstan-forbidden-method-calls-rule. Thanks!
 */
final class NoInstantiatingTranslatableDirectlyRule extends AbstractRule
{
    private const FORBIDDEN_CLASSES = [
        TranslatableMessage::class,
    ];

    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Target instantiation via the "new" keyword.
        if (!$node->class instanceof Name) {
            return [];
        }

        // Target instantiations of translatables classes.
        if (!in_array($node->class->toString(), self::FORBIDDEN_CLASSES, true)) {
            return [];
        }

        $class = $scope->getClassReflection();
        assert($class instanceof ClassReflection);

        // Exempt the translatable factory itself.
        if ($class->getName() === TranslatableFactory::class) {
            return [];
        }

        return [
            sprintf(
                'Cannot instantiate %s directly via "new" keyword or factory. Use %s instead.',
                $node->class->toString(),
                TranslatableAwareTrait::class,
            ),
        ];
    }
}
