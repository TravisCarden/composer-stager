<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Util;

use PhpTuf\ComposerStager\Tests\Functional\TestCase;
use PhpTuf\ComposerStager\Util\DirectoryUtil;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Util\DirectoryUtil
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil::ensureTrailingSlash
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil::stripTrailingSlash
 */
class DirectoryUtilTest extends TestCase
{
    /**
     * @covers ::stripTrailingSlash
     *
     * @dataProvider providerStripTrailingSlash
     */
    public function testStripTrailingSlash($givenPath, $expectedPath): void
    {
        $actual = DirectoryUtil::stripTrailingSlash($givenPath);

        self::assertEquals($expectedPath, $actual);
    }

    public function providerStripTrailingSlash(): array
    {
        return [
            [
                'givenPath' => '',
                'expectedPath' => '',
            ],
            // UNIX-like paths:
            [
                'givenPath' => './',
                'expectedPath' => '.',
            ],
            [
                'givenPath' => '/',
                'expectedPath' => '/',
            ],
            [
                'givenPath' => '/lorem/ipsum',
                'expectedPath' => '/lorem/ipsum',
            ],
            [
                'givenPath' => '/lorem/ipsum/',
                'expectedPath' => '/lorem/ipsum',
            ],
            // Traditional DOS paths:
            [
                'givenPath' => '.\\',
                'expectedPath' => '.',
            ],
            [
                'givenPath' => 'C:\\',
                'expectedPath' => 'C:\\',
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum',
                'expectedPath' => 'C:\Lorem\Ipsum',
            ],
            [
                'givenPath' => 'h:\Lorem\Ipsum\\',
                'expectedPath' => 'h:\Lorem\Ipsum',
            ],
            [
                'givenPath' => 'h:',
                'expectedPath' => 'h:',
            ],
        ];
    }

    /**
     * @covers ::ensureTrailingSlash
     * @covers ::stripTrailingSlash
     *
     * @dataProvider providerEnsureTrailingSlash
     */
    public function testEnsureTrailingSlash($givenPath, $expectedPath): void
    {
        self::fixSeparatorsMultiple($givenPath, $expectedPath);

        $actual = DirectoryUtil::ensureTrailingSlash($givenPath);

        self::assertEquals($expectedPath, $actual);
    }

    public function providerEnsureTrailingSlash(): array
    {
        return [
            [
                'givenPath' => '',
                'expectedPath' => './',
            ],
            [
                'givenPath' => '.',
                'expectedPath' => './',
            ],
            [
                'givenPath' => '/lorem/ipsum',
                'expectedPath' => '/lorem/ipsum/',
            ],
            [
                'givenPath' => '/lorem/ipsum/',
                'expectedPath' => '/lorem/ipsum/',
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum\\',
                'expectedPath' => 'C:\Lorem\Ipsum\\',
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum\\',
                'expectedPath' => 'C:\Lorem\Ipsum\\',
            ],
        ];
    }

    /**
     * @covers ::getPathRelativeToAncestor
     *
     * @dataProvider providerGetPathRelativeToAncestor
     */
    public function testGetPathRelativeToAncestor($path, $ancestor, $expected): void
    {
        $actual = DirectoryUtil::getPathRelativeToAncestor($path, $ancestor);

        self::assertEquals($expected, $actual);
    }

    public function providerGetPathRelativeToAncestor(): array
    {
        // UNIX-like OS paths.
        if (!self::isWindows()) {
            return [
                [
                    'path' => '',
                    'ancestor' => '',
                    'expected' => '',
                ],
                [
                    'path' => 'lorem',
                    'ancestor' => 'ipsum',
                    'expected' => 'lorem',
                ],
                [
                    'path' => 'lorem/ipsum',
                    'ancestor' => 'lorem',
                    'expected' => 'ipsum',
                ],
                [
                    'path' => 'lorem/ipsum/dolor/sit/amet',
                    'ancestor' => 'lorem/ipsum',
                    'expected' => 'dolor/sit/amet',
                ],
                [
                    'path' => 'lorem/ipsum/dolor/sit/amet',
                    'ancestor' => 'ipsum/dolor',
                    'expected' => 'lorem/ipsum/dolor/sit/amet',
                ],
                [
                    'path' => 'lorem/ipsum/dolor/sit/amet',
                    'ancestor' => '/lorem/ipsum',
                    'expected' => 'lorem/ipsum/dolor/sit/amet',
                ],
                [
                    'path' => '/lorem/ipsum/dolor/sit/amet',
                    'ancestor' => 'lorem/ipsum',
                    'expected' => '/lorem/ipsum/dolor/sit/amet',
                ],
            ];
        }
        // Windows paths.
        return [
            [
                'path' => '',
                'ancestor' => '',
                'expected' => '',
            ],
            [
                'path' => 'Lorem',
                'ancestor' => 'Ipsum',
                'expected' => 'Lorem',
            ],
            [
                'path' => 'Lorem\\Ipsum',
                'ancestor' => 'Lorem',
                'expected' => 'Ipsum',
            ],
            [
                'path' => 'Lorem\Ipsum\Dolor\Sit\Amet',
                'ancestor' => 'Lorem\Ipsum',
                'expected' => 'Dolor\Sit\Amet',
            ],
            [
                'path' => 'C:\Lorem\Ipsum\Dolor\Sit',
                'ancestor' => 'Lorem\Ipsum',
                'expected' => 'C:\Lorem\Ipsum\Dolor\Sit',
            ],
            [
                'path' => 'C:\Lorem\Ipsum\Dolor\Sit',
                'ancestor' => 'C:\Lorem\Ipsum',
                'expected' => 'Dolor\Sit',
            ],
        ];
    }
}
