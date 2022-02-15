<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Domain\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Domain\Exception\FileNotFoundException;
use PhpTuf\ComposerStager\Domain\Exception\PathException;
use PHPUnit\Framework\TestCase;

class PathExceptionsUnitTest extends TestCase
{
    /**
     * @covers \PhpTuf\ComposerStager\Domain\Exception\DirectoryAlreadyExistsException
     * @covers \PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException
     * @covers \PhpTuf\ComposerStager\Domain\Exception\DirectoryNotWritableException
     * @covers \PhpTuf\ComposerStager\Domain\Exception\FileNotFoundException
     * @covers \PhpTuf\ComposerStager\Domain\Exception\PathException
     *
     * @dataProvider provider
     */
    public function test(
        $args,
        $path,
        $expectedPathMessage,
        $expectedDirectoryAlreadyExistsMessage,
        $expectedDirectoryNotFoundMessage,
        $expectedDirectoryNotWritableMessage,
        $expectedFileNotFoundMessage
    ): void {
        $pathException = new PathException(...$args);
        $directoryAlreadyExistsException = new DirectoryAlreadyExistsException(...$args);
        $directoryNotFoundException = new DirectoryNotFoundException(...$args);
        $directoryNotWritableException = new DirectoryNotWritableException(...$args);
        $fileNotFoundException = new FileNotFoundException(...$args);

        self::assertSame($expectedPathMessage, $pathException->getMessage());
        self::assertSame($path, $pathException->getPath());
        self::assertSame($expectedDirectoryAlreadyExistsMessage, $directoryAlreadyExistsException->getMessage());
        self::assertSame($expectedDirectoryNotFoundMessage, $directoryNotFoundException->getMessage());
        self::assertSame($expectedDirectoryNotWritableMessage, $directoryNotWritableException->getMessage());
        self::assertSame($expectedFileNotFoundMessage, $fileNotFoundException->getMessage());
    }

    public function provider(): array
    {
        return [
            // Defaults.
            [
                'args' => ['/one'],
                'path' => '/one',
                'expectedPathMessage' => '',
                'expectedDirectoryAlreadyExistsMessage' => 'Directory already exists: "/one"',
                'expectedDirectoryNotFoundMessage' => 'No such directory: "/one"',
                'expectedDirectoryNotWritableMessage' => 'Directory not writable: "/one"',
                'expectedFileNotFoundMessage' => 'No such file: "/one"',
            ],
            // Completely override message.
            [
                'args' => ['/two', 'Example message'],
                'path' => '/two',
                'expectedPathMessage' => 'Example message',
                'expectedDirectoryAlreadyExistsMessage' => 'Example message',
                'expectedDirectoryNotFound' => 'Example message',
                'expectedDirectoryNotWritableMessage' => 'Example message',
                'expectedFileNotFound' => 'Example message',
            ],
            // Override message with path substitution.
            [
                'args' => ['/three/four', 'Example message: "%s"'],
                'path' => '/three/four',
                'expectedDirectoryAlreadyExistsMessage' => 'Example message: "/three/four"',
                'expectedPathMessage' => 'Example message: "/three/four"',
                'expectedDirectoryNotFound' => 'Example message: "/three/four"',
                'expectedDirectoryNotWritableMessage' => 'Example message: "/three/four"',
                'expectedFileNotFound' => 'Example message: "/three/four"',
            ],
        ];
    }
}
