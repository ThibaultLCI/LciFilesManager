<?php

namespace App\Command;

use App\Service\DivaltoSiteService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'get:crmSites',
    description: 'Call api au crm pour ajout/modification des dossier sur le/les serveur(s)',
)]
class GetCrmSitesCommand extends Command
{
    public function __construct(private DivaltoSiteService $divaltoSiteService, private LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Command get:crmSites start');

        $io = new SymfonyStyle($input, $output);

        $start = microtime(true);

        $this->divaltoSiteService->fetchSites();

        $io->success('La commande fetchSites a été lancée en arrière-plan.');

        $end = microtime(true) - $start;

        $this->logger->info("temps global : " . $end . "\n");

        return Command::SUCCESS;
    }
}
