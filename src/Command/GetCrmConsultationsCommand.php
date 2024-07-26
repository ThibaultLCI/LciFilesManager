<?php

namespace App\Command;

use App\Entity\Server;
use App\Service\ConsultationFolderManagerService;
use App\Service\DivaltoConsultationService;
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
    public function __construct(private LoggerInterface $consultationLogger, private ConsultationFolderManagerService $consultationFolderManagerService, private EntityManagerInterface $em, private DivaltoConsultationService $divaltoConsultationService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->consultationLogger->info('Command de recuperation Projets/Consultation');

            $this->divaltoConsultationService->fetchConsultations();

            die;

            $server = $this->em->getRepository(Server::class)->findOneBy(['name' => "CRM"]);
            $this->consultationFolderManagerService->createOrUpdateFolderOnServer($server);
            
            $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $io->success($th->getMessage());
            return Command::FAILURE;
        }
    }
}
