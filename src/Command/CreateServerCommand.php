<?php

namespace App\Command;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'create:server',
    description: 'Creation d\'une entité serveur',
)]
class CreateServerCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure() :void
    {
        $this
        ->setName('app:create-server')
        ->setDescription('Crée un nouveau serveur')
        ->addArgument('name', InputArgument::REQUIRED, 'Nom du serveur')
        ->addArgument('host', InputArgument::REQUIRED, 'Adresse IP ou hôte du serveur')
        ->addArgument('port', InputArgument::REQUIRED, 'Port du serveur');
    }
    

    protected function execute(InputInterface $input, OutputInterface $output) :int
    {
        $name = $input->getArgument('name');
        $host = $input->getArgument('host');
        $port = $input->getArgument('port');
    
        // Créez un nouvel objet Server et persistez-le en base de données.
        $server = new Server();
        $server->setName($name);
        $server->setHost($host);
        $server->setPort($port);
    
        $entityManager = $this->em;
        $entityManager->persist($server);
        $entityManager->flush();
    
        $output->writeln('Serveur créé avec succès !');
        return Command::SUCCESS;
    }
    
}
