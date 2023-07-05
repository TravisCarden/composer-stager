<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\PhpDoc;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;

/** Enforces "@package" class annotation rules. */
final class PackageAnnotationRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Only check classes and interfaces.
        if (!$node instanceof Class_ && !$node instanceof Interface_ && !$node instanceof Node\Stmt\Trait_) {
            return [];
        }

        $class = $this->getClassReflection($node);

        if (!$class instanceof ClassReflection) {
            return [];
        }

        $reflection = $class->getNativeReflection();
        $namespace = $reflection->getNamespaceName();

        // Ignore tests.
        if ($this->isInNamespace($namespace, 'PhpTuf\\ComposerStager\\Tests\\')) {
            return [];
        }

        $docComment = $node->getDocComment();

        if (!$docComment instanceof Doc) {
            return [];
        }

        $package = '';

        foreach (explode(PHP_EOL, $docComment->getText()) as $line) {
            $lineParts = explode(' @package ', $line);

            // No matching tag found.
            if (count($lineParts) === 1) {
                continue;
            }

            // Get the @package tag value.
            $value = $lineParts[1];
            $value = str_replace('*/', '', $value);
            $package = trim($value);

            break;
        }

        $namespaceParts = explode('\\', $namespace);
        $expectedPackage = $namespaceParts[3];

        if ($package !== $expectedPackage) {
            $message =sprintf('Docblock must contain "@package %s"', $expectedPackage);

            return [$this->buildErrorMessage($message)];
        }

        return [];
    }
}
