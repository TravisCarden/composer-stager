<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase
 *
 * @covers ::__construct
 * @covers ::exitEarly
 */
final class NoSymlinksPointOutsideTheCodebaseFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoSymlinksPointOutsideTheCodebase
    {
        $container = $this->container();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase $sut */
        $sut = $container->get(NoSymlinksPointOutsideTheCodebase::class);

        return $sut;
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerFulfilledWithValidLink
     */
    public function testFulfilledWithValidLink(string $link, string $target): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $link = PathFactory::create($link, $activeDirPath)->absolute();
        self::ensureParentDirectory($link);
        $target = PathFactory::create($target, $activeDirPath)->absolute();
        self::ensureParentDirectory($target);
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled, 'Allowed link pointing within the codebase.');
    }

    public function providerFulfilledWithValidLink(): array
    {
        return [
            'Not in any package' => [
                'link' => 'link.txt',
                'target' => 'target.txt',
            ],
            'Pointing within a package' => [
                'link' => 'vendor/package/link.txt',
                'target' => 'vendor/package/target.txt',
            ],
            'Pointing into a package' => [
                'link' => 'link.txt',
                'target' => 'vendor/package/target.txt',
            ],
            'Pointing out of a package' => [
                'link' => 'vendor/package/link.txt',
                'target' => 'target.txt',
            ],
            'Pointing from one package to another' => [
                'link' => 'vendor/package1/link.txt',
                'target' => 'vendor/package2/target.txt',
            ],
            'Weird relative paths' => [
                'link' => 'some/absurd/subdirectory/../with/../../a/link.txt',
                'target' => 'another/../weird/../arbitrary/target.txt',
            ],
        ];
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $targetDir, string $linkDir, string $linkDirName): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $target = PathHelper::makeAbsolute('target.txt', $targetDir);
        $link = PathHelper::makeAbsolute('link.txt', $linkDir);
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains links that point outside the codebase, which is not supported. The first one is %s.',
            $linkDirName,
            PathHelper::makeAbsolute($linkDir, getcwd()),
            $link,
        );
        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'targetDir' => PathHelper::testWorkingDirAbsolute(),
                'linkDir' => PathHelper::activeDirAbsolute(),
                'linkDirName' => 'active',
            ],
            'In staging directory' => [
                'targetDir' => PathHelper::testWorkingDirAbsolute(),
                'linkDir' => PathHelper::stagingDirAbsolute(),
                'linkDirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::isFulfilled
     *
     * @dataProvider providerFulfilledDirectoryDoesNotExist
     */
    public function testFulfilledDirectoryDoesNotExist(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        $this->doTestFulfilledDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     */
    public function testWithHardLink(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $basePathAbsolute = PathHelper::activeDirAbsolute();
        $link = PathHelper::makeAbsolute('link.txt', $basePathAbsolute);
        $target = PathHelper::makeAbsolute('target.txt', $basePathAbsolute);
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled, 'Ignored hard link link.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::linkPointsOutsidePath
     */
    public function testWithAbsoluteLink(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $dirPath = self::activeDirPath();
        $link = PathFactory::create('link.txt', $dirPath)->absolute();
        $target = PathFactory::create('target.txt', $dirPath)->absolute();
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled, 'Ignored hard link link.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerExclusions
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $targetFile = '../';
        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList(...$exclusions);
        $dirPath = PathHelper::activeDirAbsolute();
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath, $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
