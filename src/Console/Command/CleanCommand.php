<?php

namespace PhpTuf\ComposerStager\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends Command
{
    protected static $defaultName = 'clean';

    protected function configure(): void
    {
        $this
            ->setDescription('Removes the staging directory')
            ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return StatusCode::OK;
    }
}
