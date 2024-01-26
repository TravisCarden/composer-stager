<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Exception;

use Exception;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Service\TestTranslator;
use ReflectionClass;

final class ExceptionUnitTest extends TestCase
{
    /**
     * @covers \PhpTuf\ComposerStager\API\Exception\InvalidArgumentException
     * @covers \PhpTuf\ComposerStager\API\Exception\IOException
     * @covers \PhpTuf\ComposerStager\API\Exception\LogicException
     * @covers \PhpTuf\ComposerStager\API\Exception\RuntimeException
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(string $exception): void
    {
        $message = $exception;
        $translatableMessage = new TranslatableMessage($message, new TestTranslator());
        $code = 42;
        $previous = new Exception('Message');

        $sut = new $exception($translatableMessage, $code, $previous);
        assert($sut instanceof ExceptionInterface);

        self::assertSame($message, $sut->getMessage(), 'Got untranslated message.');
        self::assertSame($translatableMessage, $sut->getTranslatableMessage(), 'Got translatable message.');
        self::assertEquals($code, $sut->getCode(), 'Got code.');
        self::assertSame($previous, $sut->getPrevious(), 'Got previous exception.');
    }

    /** Provides a list of all exception classes except PreconditionException, which has a different signature. */
    public function providerBasicFunctionality(): array
    {
        $exceptions = [
            InvalidArgumentException::class,
            IOException::class,
            LogicException::class,
            RuntimeException::class,
        ];

        $data = [];

        // Give data sets a key of the class short names, rather than FQNs, for readability in test results.
        foreach ($exceptions as $class) {
            $reflection = new ReflectionClass($class);

            $data[$reflection->getShortName()] = [$class];
        }

        return $data;
    }
}
