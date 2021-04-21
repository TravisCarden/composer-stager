<?php

namespace PhpTuf\ComposerStager\Tests\Console\Command;

use PhpTuf\ComposerStager\Console\Command\CleanCommand;
use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Tests\Console\CommandTestCase;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Command\CleanCommand
 * @uses \PhpTuf\ComposerStager\Console\Application
 * @uses \PhpTuf\ComposerStager\Console\Command\CleanCommand
 * @uses \PhpTuf\ComposerStager\Console\GlobalOptions
 */
class CleanCommandTest extends CommandTestCase
{
    protected function createSut(): Command
    {
        return new CleanCommand();
    }

    /**
     * @covers ::configure
     */
    public function testBasicConfiguration(): void
    {
        $sut = $this->createSut();

        $definition = $sut->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();

        self::assertSame('clean', $sut->getName(), 'Set correct name.');
        self::assertSame([], $sut->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($sut->getDescription(), 'Set a description.');
        self::assertSame([], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
    }

    /**
     * @covers ::execute
     */
    public function testBasicExecution(): void
    {
        $this->executeCommand();

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(ExitCode::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }
}
