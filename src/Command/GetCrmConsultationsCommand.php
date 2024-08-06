<?php

namespace App\Command;

use App\Entity\Server;
use App\Service\DivaltoConsultationService;
use App\Service\DivaltoProjetHasConsultationService;
use App\Service\DivaltoProjetService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'get:crmConsultations',
    description: 'Add a short description for your command',
)]
class GetCrmConsultationsCommand extends Command
{
    public function __construct(private LoggerInterface $consultationLogger, private EntityManagerInterface $em, private DivaltoConsultationService $divaltoConsultationService, private LoggerInterface $projetLogger, private DivaltoProjetService $divaltoProjetService,  private LoggerInterface $projetHasConsultationLogger, private DivaltoProjetHasConsultationService $divaltoProjetHasConsultationService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $serverCommerial = $this->em->getRepository(Server::class)->findOneBy(['name' => 'commercial']);

        if ($serverCommerial) {
            try {
                $this->consultationLogger->info('Command de recuperation Consultations');
                $this->divaltoConsultationService->fetchConsultations();

                $this->projetLogger->info('Command de recuperation Projets');
                $this->divaltoProjetService->fetchProjets();

                $this->projetHasConsultationLogger->info('Command de recuperation Relation projets Consultations');
                $this->divaltoProjetHasConsultationService->fetchRelations();

                $io->success('Syncronisation des projet et des consultation reussi');

                return Command::SUCCESS;
            } catch (\Throwable $th) {
                $io->error($th->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->error("Server commercial non configuré");
            return Command::FAILURE;
        }
    }
}
