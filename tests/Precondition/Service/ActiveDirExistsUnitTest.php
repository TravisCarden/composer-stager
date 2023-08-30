<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirExists;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirExists
 *
 * @covers ::__construct
 */
final class ActiveDirExistsUnitTest extends PreconditionTestCase
{
    private FilesystemInterface|ObjectProphecy $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);

        parent::setUp();
    }

    protected function createSut(): ActiveDirExists
    {
        $environment = $this->environment->reveal();
        $filesystem = $this->filesystem->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new ActiveDirExists($environment, $filesystem, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->filesystem
            ->exists(PathHelper::activeDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled('The active directory exists.');
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = 'The active directory does not exist.';
        $this->filesystem
            ->exists(PathHelper::activeDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
