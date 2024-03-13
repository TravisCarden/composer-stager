<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\NoNestingOnWindows;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoNestingOnWindows
 *
 * @covers ::__construct
 * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::getStatusMessage
 */
final class NoNestingOnWindowsUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Active and staging directories not nested on Windows.';
    protected const DESCRIPTION = 'The active and staging directories cannot be nested if on Windows.';
    protected const FULFILLED_STATUS_MESSAGE = 'The active and staging directories are not nested if on Windows.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->environment
            ->isWindows()
            ->willReturn(true);
    }

    protected function createSut(): NoNestingOnWindows
    {
        $environment = $this->environment->reveal();
        $pathHelper = self::createPathHelper();
        $translatableFactory = self::createTranslatableFactory();

        return new NoNestingOnWindows($environment, $pathHelper, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testExitEarlyOnNonWindows(): void
    {
        $activeDirPath = self::createPath('/active-dir');
        $stagingDirPath = self::createPath('/active-dir/staging-dir');

        $this->environment
            ->isWindows()
            ->willReturn(false);

        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled, 'Exited early on non-Windows');
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $activeDir, string $stagingDir): void
    {
        $activeDirPath = self::createPath($activeDir);
        $stagingDirPath = self::createPath($stagingDir);

        $expectedStatusMessage = sprintf(
            'The active and staging directories cannot be nested at %s and %s, respectively.',
            $activeDir,
            $stagingDir,
        );

        $this->doTestUnfulfilled($expectedStatusMessage, null, $activeDirPath, $stagingDirPath);
    }

    public function providerUnfulfilled(): array
    {
        return [
            [
                'activeDir' => '/active-dir',
                'stagingDir' => '/active-dir/staging-dir',
            ],
            [
                'activeDir' => '/staging-dir',
                'stagingDir' => '/staging-dir/active-dir',
            ],
        ];
    }
}
