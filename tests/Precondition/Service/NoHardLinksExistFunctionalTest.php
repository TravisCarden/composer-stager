<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use Symfony\Component\Filesystem\Path;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist
 *
 * @covers ::__construct
 */
final class NoHardLinksExistFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoHardLinksExist
    {
        return ContainerTestHelper::get(NoHardLinksExist::class);
    }

    /** @covers ::assertIsSupportedFile */
    public function testFulfilledWithSymlink(): void
    {
        $target = Path::makeAbsolute('target.txt', self::activeDirAbsolute());
        $link = Path::makeAbsolute('link.txt', self::activeDirAbsolute());
        FilesystemTestHelper::touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

        self::assertTrue($isFulfilled, 'Allowed symlink.');
    }

    /**
     * @covers ::assertIsSupportedFile
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $directory, string $dirName): void
    {
        $target = self::makeAbsolute('target.txt', $directory);
        $link = self::makeAbsolute('link.txt', $directory);
        FilesystemTestHelper::touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains hard links, which is not supported. The first one is %s.',
            $dirName,
            $directory,
            $link,
        );
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'directory' => self::activeDirAbsolute(),
                'dirName' => 'active',
            ],
            'In staging directory' => [
                'directory' => self::stagingDirAbsolute(),
                'dirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::assertIsSupportedFile
     *
     * @dataProvider providerExclusions
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = 'target.txt';

        // The target file is effectively a link just as much as the source, because
        // it has an nlink value of greater than one. So it must be excluded, too.
        $exclusions[] = $targetFile;

        $links = array_fill_keys($links, $targetFile);
        $exclusions = self::createPathList(...$exclusions);
        $dirPath = self::activeDirAbsolute();
        self::createFile($dirPath, $targetFile);
        FilesystemTestHelper::createHardlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
