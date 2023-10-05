<?php

namespace App\Command;

use App\Service\DivaltoSiteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'clear:crmSites',
    description: 'supprime tout les sites enregistré en base',
)]
class ClearCrmSitesCommand extends Command
{
    public function __construct(private DivaltoSiteService $divaltoSiteService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $response = $this->divaltoSiteService->clearSites();

        $io->success(json_decode($response->getContent()));

        return Command::SUCCESS ;
    }
}
