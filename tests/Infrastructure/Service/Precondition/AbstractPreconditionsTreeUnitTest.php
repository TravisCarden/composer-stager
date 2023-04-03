<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\PathList\TestPathList;
use PhpTuf\ComposerStager\Tests\TestSpyInterface;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface $exclusions
 */
final class AbstractPreconditionsTreeUnitTest extends PreconditionTestCase
{
    protected function createSut(...$children): AbstractPreconditionsTree
    {
        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class (...$children) extends AbstractPreconditionsTree
        {
            public string $name = 'Name';
            public string $description = 'Description';
            public bool $isFulfilled = true;
            public string $fulfilledStatusMessage = 'Fulfilled';
            public string $unfulfilledStatusMessage = 'Unfulfilled';

            public function getName(): string
            {
                return $this->name;
            }

            public function getDescription(): string
            {
                return $this->description;
            }

            protected function getFulfilledStatusMessage(): string
            {
                return $this->fulfilledStatusMessage;
            }

            protected function getUnfulfilledStatusMessage(): string
            {
                return $this->unfulfilledStatusMessage;
            }
        };
    }

    /**
     * @covers ::getDescription
     * @covers ::getLeaves
     * @covers ::getName
     * @covers ::getStatusMessage
     *
     * @dataProvider providerBasicFunctionality
     *
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function testBasicFunctionality(
        $name,
        $description,
        $isFulfilled,
        $fulfilledStatusMessage,
        $unfulfilledStatusMessage,
        $expectedStatusMessage,
        $exclusions,
    ): void {
        // Pass a mock child into the SUT so the behavior of ::assertIsFulfilled
        // can be controlled indirectly, without overriding the method on the SUT
        // itself and preventing it from actually being exercised.
        /** @var \PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface|\Prophecy\Prophecy\ObjectProphecy $child */
        $child = $this->prophesize(PreconditionInterface::class);
        $child->getLeaves()
            ->willReturn([$child]);

        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $child->assertIsFulfilled($this->activeDir, $this->stagingDir, $exclusions)
            ->shouldBeCalledTimes(2);

        if (!$isFulfilled) {
            $child->assertIsFulfilled($this->activeDir, $this->stagingDir, $exclusions)
                ->willThrow(PreconditionException::class);
        }

        $child = $child->reveal();

        $sut = $this->createSut($child);

        $sut->name = $name;
        $sut->description = $description;
        $sut->isFulfilled = $isFulfilled;
        $sut->fulfilledStatusMessage = $fulfilledStatusMessage;
        $sut->unfulfilledStatusMessage = $unfulfilledStatusMessage;

        self::assertEquals($sut->getName(), $name);
        self::assertEquals($sut->getDescription(), $description);
        self::assertEquals($sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions), $isFulfilled);
        self::assertEquals($sut->getStatusMessage($this->activeDir, $this->stagingDir, $exclusions), $expectedStatusMessage);
    }

    public function providerBasicFunctionality(): array
    {
        return [
            [
                'name' => 'Name 1',
                'description' => 'Description 1',
                'isFulfilled' => true,
                'fulfilledStatusMessage' => 'Fulfilled status message 1',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 1',
                'expectedStatusMessage' => 'Fulfilled status message 1',
                'exclusions' => null,
            ],
            [
                'name' => 'Name 2',
                'description' => 'Description 2',
                'isFulfilled' => false,
                'fulfilledStatusMessage' => 'Fulfilled status message 2',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 2',
                'expectedStatusMessage' => 'Unfulfilled status message 2',
                'exclusions' => new TestPathList(),
            ],
        ];
    }

    /** @covers ::getLeaves */
    public function testIsFulfilledBubbling(): void
    {
        $message = 'Lorem ipsum';

        $this->expectException(PreconditionException::class);
        $this->expectExceptionMessage($message);

        $createLeaf = function (bool $isFulfilled) use ($message): PreconditionInterface {
            /** @var \Prophecy\Prophecy\ObjectProphecy|\PhpTuf\ComposerStager\Tests\TestSpyInterface $spy */
            $spy = $this->prophesize(TestSpyInterface::class);
            $spy->report()
                // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
                ->shouldBeCalledTimes(2);
            $spy = $spy->reveal();

            return new Class($isFulfilled, $message, $spy) extends AbstractPrecondition
            {
                public function __construct(
                    private readonly bool $isFulfilled,
                    private readonly string $message,
                    private readonly TestSpyInterface $spy,
                ) {
                }

                protected function getFulfilledStatusMessage(): string
                {
                    return '';
                }

                protected function getUnfulfilledStatusMessage(): string
                {
                    return $this->message;
                }

                public function getName(): string
                {
                    return '';
                }

                public function getDescription(): string
                {
                    return '';
                }

                public function isFulfilled(
                    PathInterface $activeDir,
                    PathInterface $stagingDir,
                    ?PathListInterface $exclusions = null,
                ): bool {
                    $this->spy->report();

                    return $this->isFulfilled;
                }
            };
        };

        $leaves = [
            $createLeaf(true),
            $createLeaf(true),
            $createLeaf(true),
            $createLeaf(false),
        ];

        // phpcs:disable SlevomatCodingStandard.Functions.RequireTrailingCommaInCall.MissingTrailingComma
        //   Trailing commas on this array make it cross PhpStorm's complexity threshold:
        //   "Code fragment is too complex to parse and will be treated as plain text."
        $sut = $this->createSut(
            $leaves[0],
            $this->createSut(
                $this->createSut(
                    $leaves[1],
                )
            ),
            $this->createSut(
                $this->createSut(
                    $this->createSut(
                        $this->createSut(
                            $leaves[2],
                        )
                    )
                )
            ),
            $this->createSut(
                $this->createSut(
                    $this->createSut(
                        $this->createSut(
                            $this->createSut(
                                $this->createSut(
                                    $this->createSut(
                                        $this->createSut(
                                            $this->createSut(
                                                $this->createSut(
                                                    $leaves[3],
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
        // phpcs:enable

        self::assertFalse($sut->isFulfilled($this->activeDir, $this->stagingDir));
        self::assertSame($leaves, $sut->getLeaves());

        // This is called last so as not to throw the exception until all other
        // assertions have been made.
        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }
}
