<?php

namespace App\Command;

use App\Repository\GithubParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixRepositoriesCommand extends Command
{
    protected static $defaultName = 'app:fix-repositories';

    private GithubParser $githubParser;

    public function __construct(GithubParser $githubParser)
    {
        $this->githubParser = $githubParser;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Check every registered repository to look for missing updates')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only display missing updates')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->githubParser->check($io);


        if ($input->getOption('dry-run')) {
            return Command::SUCCESS;
        }

        $io->success('Successfully processed all files in all repositories.');

        return Command::SUCCESS;
    }
}
