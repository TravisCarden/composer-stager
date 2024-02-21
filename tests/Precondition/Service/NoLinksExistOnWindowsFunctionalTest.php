<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows
 *
 * @covers ::__construct
 */
final class NoLinksExistOnWindowsFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoLinksExistOnWindows
    {
        return ContainerTestHelper::get(NoLinksExistOnWindows::class);
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::exitEarly
     *
     * @dataProvider providerUnfulfilled
     *
     * @group windows_only
     */
    public function testUnfulfilled(array $symlinks, array $hardLinks): void
    {
        $activeDirPath = PathTestHelper::activeDirPath();
        $stagingDirPath = PathTestHelper::stagingDirPath();

        $basePathAbsolute = PathTestHelper::activeDirAbsolute();
        $link = PathTestHelper::makeAbsolute('link.txt', $basePathAbsolute);
        $target = PathTestHelper::makeAbsolute('target.txt', $basePathAbsolute);
        FilesystemTestHelper::touch($target);
        FilesystemTestHelper::createSymlinks($basePathAbsolute, $symlinks);
        FilesystemTestHelper::createHardlinks($basePathAbsolute, $hardLinks);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        self::assertFalse($isFulfilled, 'Rejected link on Windows.');

        $message = sprintf(
            'The active directory at %s contains links, which is not supported on Windows. The first one is %s.',
            $basePathAbsolute,
            $link,
        );
        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'Contains symlink' => [
                'symlinks' => ['link.txt' => 'target.txt'],
                'hardLinks' => [],
            ],
            'Contains hard link' => [
                'symlinks' => [],
                'hardLinks' => ['link.txt' => 'target.txt'],
            ],
        ];
    }

    /**
     * @covers ::exitEarly
     * @covers ::isFulfilled
     *
     * @dataProvider providerExclusions
     *
     * @group windows_only
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = 'target.txt';
        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList(...$exclusions);
        $basePath = PathTestHelper::activeDirAbsolute();
        self::createFile($basePath, $targetFile);
        FilesystemTestHelper::createSymlinks($basePath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
