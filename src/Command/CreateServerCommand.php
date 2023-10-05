<?php

namespace App\Command;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'create:server',
    description: 'Lancer un prompt pour la creation d\'un nouveau serveur',
)]
class CreateServerCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new server')
            ->setHelp('This command allows you to create a new server interactively.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        
        // Ask for server name
        $nameQuestion = new Question('Enter the server name: ');
        $name = $helper->ask($input, $output, $nameQuestion);
        
        // Ask for server host
        $hostQuestion = new Question('Enter the server host: ');
        $host = $helper->ask($input, $output, $hostQuestion);
        
        // Ask for server port
        $portQuestion = new Question('Enter the server port: ');
        $port = $helper->ask($input, $output, $portQuestion);
    
        // Create a new Server object and persist it to the database
        $server = new Server();
        $server->setName($name);
        $server->setHost($host);
        $server->setPort($port);
    
        $this->entityManager->persist($server);
        $this->entityManager->flush();
    
        $output->writeln('Serveur créé avec succès !');
        return Command::SUCCESS;
    }
}
