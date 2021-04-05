<?php

namespace PhpTuf\ComposerStager\Tests\Console\Command;

use PhpTuf\ComposerStager\Console\Command\CleanCommand;
use PhpTuf\ComposerStager\Console\Command\StatusCode;
use PhpTuf\ComposerStager\Tests\Console\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Command\CleanCommand
 * @uses \PhpTuf\ComposerStager\Console\Command\CleanCommand
 */
class CleanCommandTest extends CommandTestBase
{
    protected function createCommand(): Command
    {
        return new CleanCommand();
    }

    /**
     * @covers ::configure
     */
    public function testBasicConfiguration(): void
    {
        $command = $this->createCommand();

        $definition = $command->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();

        self::assertEquals('clean', $command->getName(), 'Set correct name.');
        self::assertEquals([], $command->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($command->getDescription(), 'Set a description.');
        self::assertEquals([], array_keys($arguments), 'Set correct arguments.');
        self::assertEquals([], array_keys($options), 'Set correct options.');
    }

    /**
     * @covers ::execute
     */
    public function testBasicExecution(): void
    {
        $this->executeCommand();

        self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
        self::assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
    }
}
