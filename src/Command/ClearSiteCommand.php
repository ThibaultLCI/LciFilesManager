<?php

namespace App\Command;

use App\Service\ApiDivalto\DivaltoSiteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'clearSiteCommand',
    description: 'Supprime les sites en base de donnée et dans le dossier de l\'app',
)]
class ClearSiteCommand extends Command
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

        $response = $this->divaltoSiteService->clearSite();

        $io->success(json_decode($response->getContent()));

        return Command::SUCCESS;
    }
}
