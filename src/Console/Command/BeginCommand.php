<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Console\Output\Callback;
use PhpTuf\ComposerStager\Domain\BeginnerInterface;
use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class BeginCommand extends Command
{
    private const NAME = 'begin';

    /**
     * @var \PhpTuf\ComposerStager\Domain\BeginnerInterface
     */
    private $beginner;

    public function __construct(BeginnerInterface $beginner)
    {
        parent::__construct(self::NAME);
        $this->beginner = $beginner;
    }

    protected function configure(): void
    {
        $this->setDescription('Begins the staging process by copying the active directory to the staging directory');
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $activeDir */
        $activeDir = $input->getOption(Application::ACTIVE_DIR_OPTION);
        /** @var string $stagingDir */
        $stagingDir = $input->getOption(Application::STAGING_DIR_OPTION);

        try {
            $this->beginner->begin(
                $activeDir,
                $stagingDir,
                new Callback($input, $output)
            );

            return ExitCode::SUCCESS;
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return ExitCode::FAILURE;
        }
    }
}
