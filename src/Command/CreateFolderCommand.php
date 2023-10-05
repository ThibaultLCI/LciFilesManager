<?php

namespace App\Command;

use App\Entity\Folder;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'create:folder',
    description: 'Create a new folder interactively',
)]
class CreateFolderCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new folder')
            ->setHelp('This command allows you to create a new folder interactively.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Ask for folder path
        $path = $io->ask('Enter the folder path:', null, function ($path) {
            if (empty($path)) {
                throw new \RuntimeException('The folder path cannot be empty.');
            }
            return $path;
        });

        // Get a list of available servers
        $servers = $this->entityManager->getRepository(Server::class)->findAll();
        $serverChoices = [];

        foreach ($servers as $server) {
            $serverChoices[$server->getName()] = $server->getId();
        }

        // Ask the user to select a server
        $serverQuestion = new ChoiceQuestion('Select the server for this folder:', array_keys($serverChoices));
        $selectedServerName = $io->askQuestion($serverQuestion);

        // Find the selected server by its name
        $selectedServerId = $serverChoices[$selectedServerName];
        $selectedServer = $this->entityManager->getRepository(Server::class)->find($selectedServerId);

        // Create a new Folder object and set the path and server
        $folder = new Folder();
        $folder->setPath($path);
        $folder->setServer($selectedServer);

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        $io->success('Folder created successfully!');
        return Command::SUCCESS;
    }
}
