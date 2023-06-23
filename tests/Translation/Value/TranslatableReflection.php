<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use ReflectionClass;

final class TranslatableReflection
{
    private readonly ReflectionClass $reflection;

    public function __construct(private readonly TranslatableInterface $translatable)
    {
        $this->reflection = new ReflectionClass($this->translatable);
    }

    public function getProperties(): array
    {
        return [
            'message' => $this->getPropertyValue('message'),
            'parameters' => $this->getPropertyValue('parameters'),
            'domain' => $this->getPropertyValue('domain'),
        ];
    }

    private function getPropertyValue(string $name): mixed
    {
        $property = $this->reflection
            ->getProperty($name)
            ->getValue($this->translatable);

        if ($name === 'parameters') {
            return $this->getParametersAsArray($property);
        }

        return $property;
    }

    public function getParametersAsArray(?TranslationParametersInterface $property): array
    {
        return $property instanceof TranslationParametersInterface
            ? $property->getAll()
            : [];
    }
}
