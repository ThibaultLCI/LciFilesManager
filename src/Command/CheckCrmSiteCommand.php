<?php

namespace App\Command;

use App\Service\DivaltoSiteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'checkCrmSite',
    description: 'Call api au crm pour ajout/modification des dossier sur le/les serveur(s)',
)]
class CheckCrmSiteCommand extends Command
{
    public function __construct(private DivaltoSiteService $divaltoSiteService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $start = microtime(true);

        $response = $this->divaltoSiteService->fetchSites();

        $io->success(json_decode($response->getContent()));

        $end = microtime(true) - $start;

        echo $end;

        return Command::SUCCESS;
    }
}
