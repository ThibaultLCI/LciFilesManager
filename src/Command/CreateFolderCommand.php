<?php

namespace App\Command;

use App\Entity\Folder;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'create:folder',
    description: 'Creation d\'une entité dossier',
)]
class CreateFolderCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:create-folder')
            ->setDescription('Crée un nouveau dossier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Demandez le chemin du dossier.
        $path = $io->ask('Chemin du dossier :', null, function ($path) {
            if (empty($path)) {
                throw new \RuntimeException('Le chemin du dossier ne peut pas être vide.');
            }
            return $path;
        });

        // Récupérez la liste des serveurs disponibles depuis la base de données.
        $entityManager = $this->em;
        $servers = $entityManager->getRepository(Server::class)->findAll();

        if (empty($servers)) {
            $io->error('Aucun serveur disponible.');
            return Command::FAILURE;
        }

        $serverChoices = [];

        // Construisez un tableau de choix pour la question de choix.
        foreach ($servers as $server) {
            $serverChoices[$server->getId()] = sprintf('%s (%s:%d)', $server->getName(), $server->getHost(), $server->getPort());
        }

        // Créez une question de choix pour sélectionner un serveur.
        $question = new ChoiceQuestion('Sélectionnez un serveur :', array_values($serverChoices));
        $question->setErrorMessage('Serveur invalide.');
        $selectedServer = $io->askQuestion($question);

        // Récupérez l'ID du serveur sélectionné.
        $serverId = array_search($selectedServer, $serverChoices);

        // Créez un nouveau dossier et associez-le au serveur.
        $folder = new Folder();
        $folder->setPath($path);
        $folder->setServer($server);

        $entityManager->persist($folder);
        $entityManager->flush();

        $io->success('Dossier créé avec succès !');
        return Command::SUCCESS;
    }
}
