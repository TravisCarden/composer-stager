<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path\TestPath;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\Translation\TestTranslatableMessage;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @coversNothing
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
 * @property string $executableFinderClass
 */
final class ComposerIsAvailableFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
        mkdir(self::STAGING_DIR, 0777, true);

        $this->activeDir = new TestPath(self::ACTIVE_DIR);
        $this->stagingDir = new TestPath(self::STAGING_DIR);
        $this->executableFinderClass = ExecutableFinder::class;
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): ComposerIsAvailable
    {
        $container = $this->getContainer();

        // Override the ExecutableFinder implementation.
        $executableFinder = new Definition($this->executableFinderClass);
        $container->setDefinition(ExecutableFinderInterface::class, $executableFinder);

        // Compile the container.
        $container->compile();

        // Get services.
        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable $sut */
        $sut = $container->get(ComposerIsAvailable::class);

        return $sut;
    }

    // The happy path, which would usually have a test method here, is implicitly tested in the end-to-end test.
    // @see \PhpTuf\ComposerStager\Tests\EndToEnd\EndToEndFunctionalTestCase

    public function testComposerNotFound(): void
    {
        $this->executableFinderClass = ComposerNotFoundExecutableFinder::class;
        $sut = $this->createSut();

        $message = ComposerNotFoundExecutableFinder::EXCEPTION_MESSAGE;
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message, LogicException::class);
    }

    public function testInvalidComposerFound(): void
    {
        $this->executableFinderClass = InvalidComposerFoundExecutableFinder::class;
        $sut = $this->createSut();

        $message = InvalidComposerFoundExecutableFinder::getExceptionMessage();
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message);
    }
}

final class ComposerNotFoundExecutableFinder implements ExecutableFinderInterface
{
    public const EXCEPTION_MESSAGE = 'Cannot find Composer.';

    public function find(string $name): string
    {
        throw new LogicException(new TestTranslatableMessage(self::EXCEPTION_MESSAGE));
    }
}

final class InvalidComposerFoundExecutableFinder implements ExecutableFinderInterface
{
    public function find(string $name): string
    {
        return __FILE__;
    }

    public static function getExceptionMessage(): string
    {
        return sprintf('The Composer executable at %s is invalid.', __FILE__);
    }
}
