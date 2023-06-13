<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointToADirectory;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\PathList;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointToADirectory
 *
 * @covers ::__construct
 * @covers ::exitEarly
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\FileSyncer\FileSyncerFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslationParameters
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
 */
final class NoSymlinksPointToADirectoryFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoSymlinksPointToADirectory
    {
        $container = $this->getContainer();

        // Override the FileSyncer implementation.
        $fileSyncer = $container->getDefinition(FileSyncerInterface::class);
        $fileSyncer->setFactory(null);
        $fileSyncer->setClass(PhpFileSyncer::class);
        $container->setDefinition(FileSyncerInterface::class, $fileSyncer);

        // Compile the container.
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointToADirectory $sut */
        $sut = $container->get(NoSymlinksPointToADirectory::class);

        return $sut;
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     */
    public function testFulfilledWithValidLink(): void
    {
        $link = PathFactory::create('link.txt', $this->activeDir)->resolved();
        self::ensureParentDirectory($link);
        $target = PathFactory::create('target.txt', $this->activeDir)->resolved();
        self::ensureParentDirectory($target);
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Allowed link pointing within the codebase.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $targetDir, string $linkDir, string $linkDirName): void
    {
        $target = PathFactory::create($targetDir . '/target_directory')->resolved();
        $link = PathFactory::create($linkDir . '/directory_link')->resolved();
        mkdir($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains symlinks that point to a directory, which is not supported. The first one is %s.',
            $linkDirName,
            PathFactory::create($linkDir)->resolved(),
            $link,
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'targetDir' => self::TEST_WORKING_DIR,
                'linkDir' => self::ACTIVE_DIR,
                'linkDirName' => 'active',
            ],
            'In staging directory' => [
                'targetDir' => self::TEST_WORKING_DIR,
                'linkDir' => self::STAGING_DIR,
                'linkDirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     *
     * @dataProvider providerExclusions
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetDir = './target_directory';
        mkdir(PathFactory::create($targetDir, $this->activeDir)->resolved());
        $links = array_fill_keys($links, $targetDir);
        $exclusions = new PathList(...$exclusions);
        $dirPath = $this->activeDir->resolved();
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
