<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Tests\Precondition\Service\TestPrecondition;
use PhpTuf\ComposerStager\Tests\TestUtils\AssertTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestDomainOptions;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

abstract class TestCase extends PHPUnitTestCase
{
    use AssertTrait;
    use ProphecyTrait;

    protected const ORIGINAL_CONTENT = '';
    protected const CHANGED_CONTENT = 'changed';

    protected static function createTestEnvironment(?string $activeDir = null): void
    {
        $activeDir ??= PathHelper::activeDirRelative();

        self::removeTestEnvironment();

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $workingDirAbsolute = PathHelper::testFreshFixturesDirAbsolute();
        $activeDirAbsolute = PathHelper::makeAbsolute($activeDir, $workingDirAbsolute);
        FilesystemHelper::createDirectories([$workingDirAbsolute, $activeDirAbsolute]);
        chdir($workingDirAbsolute);
    }

    protected static function removeTestEnvironment(): void
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists(PathHelper::testFreshFixturesDirAbsolute())) {
            return;
        }

        try {
            $filesystem->remove(PathHelper::testFreshFixturesDirAbsolute());
        } catch (IOException) {
            // @todo Windows chokes on this every time, e.g.,
            //    | Failed to remove directory
            //    | "D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env-container":
            //    | rmdir(D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env-container):
            //    | Resource temporarily unavailable.
            //   Obviously, this error suppression is likely to bite us in the future
            //   even though it doesn't seem to cause any problems now. Fix it.
            // @ignoreException
        }
    }

    protected static function createFiles(string $basePath, array $filenames): void
    {
        foreach ($filenames as $filename) {
            self::createFile($basePath, $filename);
        }
    }

    protected static function createFile(string $basePath, string $filename): void
    {
        $filename = PathHelper::makeAbsolute($filename, $basePath);
        FilesystemHelper::touch($filename);
        $realpathResult = realpath($filename);

        assert($realpathResult !== false, "Got absolute path of {$filename}.");
    }

    public static function createTestPreconditionException(
        string $message = '',
        ?TranslationParametersInterface $parameters = null,
        $domain = TestDomainOptions::EXCEPTIONS,
    ): PreconditionException {
        return new PreconditionException(
            new TestPrecondition(),
            new TestTranslatableMessage(
                $message,
                $parameters,
                $domain,
            ),
        );
    }

    protected static function changeFile(string $dir, string $filename): void
    {
        $fileAbsolute = PathHelper::ensureTrailingSlash($dir) . $filename;
        $result = file_put_contents($fileAbsolute, self::CHANGED_CONTENT);
        assert($result !== false, "Changed file {$fileAbsolute}.");
    }

    protected static function deleteFile(string $dir, string $filename): void
    {
        $fileAbsolute = PathHelper::ensureTrailingSlash($dir) . $filename;
        $result = unlink($fileAbsolute);
        assert($result, "Deleted file {$fileAbsolute}.");
    }

    protected static function getDirectoryContents(string $dir): array
    {
        $dir = PathHelper::ensureTrailingSlash($dir);
        $dirListing = self::getFlatDirectoryListing($dir);

        $contents = [];

        foreach ($dirListing as $pathAbsolute) {
            if (is_link($dir . $pathAbsolute)) {
                $contents[$pathAbsolute] = '';

                continue;
            }

            $contents[$pathAbsolute] = file_get_contents($dir . $pathAbsolute);
        }

        return $contents;
    }

    protected function normalizePaths(array $paths): array
    {
        $paths = array_map(static function ($path): string {
            $path = implode(
                DIRECTORY_SEPARATOR,
                [
                    PathHelper::testFreshFixturesDirAbsolute(),
                    PathHelper::activeDirRelative(),
                    $path,
                ],
            );

            return PathHelper::makeAbsolute($path, getcwd());
        }, $paths);

        sort($paths);

        return $paths;
    }
}
