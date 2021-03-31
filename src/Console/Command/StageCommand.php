<?php

namespace PhpTuf\ComposerStager\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StageCommand extends Command
{
    protected static $defaultName = 'stage';

    protected function configure(): void
    {
        $this
            ->setDescription('Stages a Composer command')
            ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return StatusCode::OK;
    }
}
