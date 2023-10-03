<?php

namespace App\Command;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateServerCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('create:server')
            ->setDescription('Crée un nouveau serveur interactivement')
            ->setHelp('Cette commande permet de créer un nouveau serveur interactivement.')
            ->addArgument('name', InputArgument::OPTIONAL, 'Nom du serveur')
            ->addArgument('host', InputArgument::OPTIONAL, 'Adresse IP ou hôte du serveur')
            ->addArgument('port', InputArgument::OPTIONAL, 'Port du serveur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Demandez le nom du serveur s'il n'est pas fourni en argument.
        $name = $input->getArgument('name');
        if (!$name) {
            $name = $io->ask('Nom du serveur :', null, function ($name) {
                if (empty($name)) {
                    throw new \RuntimeException('Le nom du serveur ne peut pas être vide.');
                }
                return $name;
            });
        }

        // Demandez l'adresse IP ou l'hôte du serveur s'il n'est pas fourni en argument.
        $host = $input->getArgument('host');
        if (!$host) {
            $host = $io->ask('Adresse IP ou hôte du serveur :', null, function ($host) {
                if (empty($host)) {
                    throw new \RuntimeException('L\'adresse IP ou l\'hôte du serveur ne peut pas être vide.');
                }
                return $host;
            });
        }

        // Demandez le port du serveur s'il n'est pas fourni en argument.
        $port = $input->getArgument('port');
        if (!$port) {
            $port = $io->ask('Port du serveur :', null, function ($port) {
                if (empty($port)) {
                    throw new \RuntimeException('Le port du serveur ne peut pas être vide.');
                }
                return $port;
            });
        }

        // Créez un nouvel objet Server et persistez-le en base de données.
        $server = new Server();
        $server->setName($name);
        $server->setHost($host);
        $server->setPort($port);

        $entityManager = $this->em;
        $entityManager->persist($server);
        $entityManager->flush();

        $io->success('Serveur créé avec succès !');
        return Command::SUCCESS;
    }
}
