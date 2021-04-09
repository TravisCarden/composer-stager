<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommitCommand extends Command
{
    protected static $defaultName = 'commit';

    protected function configure(): void
    {
        $this
            ->setDescription('Makes the staged changes live by syncing the active directory with the staging directory')
            ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return ExitCode::SUCCESS;
    }
}
