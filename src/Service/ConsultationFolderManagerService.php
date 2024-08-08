<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Folder;
use App\Entity\Projet;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ConsultationFolderManagerService
{
    public function __construct(private LoggerInterface $folderLogger, private EntityManagerInterface $em, private SshService $sshService)
    {
    }

    function createOrUpdateFolderOnServer(array $foldersToCreate, array $foldersToUpdate, Server $server, string $parentFolder)
    {
        $commands = [];

        if (count($foldersToCreate) != 0 || count($foldersToUpdate) != 0) {

            foreach ($foldersToCreate as $folderToCreate) {
                foreach ($server->getFolders() as $folder) {
                    $commands[] = $this->generateCreateFolderCommand($folderToCreate, $folder, $parentFolder);
                }
            }

            foreach ($foldersToUpdate as $folderToUpdate) {
                foreach ($server->getFolders() as $folder) {
                    $commands[] = $this->generateEditFolderCommand($folderToUpdate, $folder, $parentFolder);
                }
            }
        }

        return $commands;
    }

    private function generateCreateFolderCommand(string $folderTocreate, Folder $folder, string $parentFolder): string
    {
        $baseFolder = $folder->getPath() . $parentFolder;

        $commands = [
            "mkdir \"$baseFolder" . $folderTocreate . "\""
        ];

        return implode(' && ', $commands);
    }

    private function generateEditFolderCommand(array $folderToUpdate, Folder $folder, string $parentFolder): string
    {
        $baseFolder = $folder->getPath() . $parentFolder;

        $newFolderName = $folderToUpdate["newName"];
        $oldFolderName = $folderToUpdate["oldName"];

        $commands = [];

        $commands[] = "rmdir \"$baseFolder$oldFolderName\"";
        $commands[] = "mkdir \"$baseFolder$newFolderName\"";

        return implode(' && ', $commands);
    }

    public function manageShortCut()
    {
        $commands = [];
        $server = $this->em->getRepository(Server::class)->findOneBy(['name' => 'commercial']);

        $projetShortcutCommands = $this->createProjetShortCutToConsultation($server);
        $consultationShortcutCommands = $this->createConsultationShortCutToProjet($server);

        $this->folderLogger->info("Les raccourcis ont eté créées/modifié");

        $commands = array_merge($projetShortcutCommands, $consultationShortcutCommands);

        return $commands;
    }

    private function createProjetShortCutToConsultation(Server $server): array
    {
        $projets = $this->em->getRepository(Projet::class)->findAll();

        $deleteCommands = [];
        $commands = [];

        foreach ($server->getFolders() as $folder) {
            $baseFolder = $folder->getPath();

            $baseProjetFolderName = $baseFolder . "\\------ PROJET CRM\\";
            $baseConsultationFolder = $baseFolder . "\\------ CONSULTATION CRM\\";

            foreach ($projets as $projet) {

                $deleteCommands[] = "for /D %i in (\"{$baseProjetFolderName}{$projet->getFolderName()}\\*\") do @fsutil reparsepoint query \"%i\" >nul 2>&1 && rmdir /s /q \"%i\"";


                foreach ($projet->getConsultations() as $key => $consultation) {
                    $consultationFolderNameTarget = $baseConsultationFolder . $consultation->getFolderName();
                    $consultationShortcutName = $baseProjetFolderName . $projet->getFolderName() . "\\" . $consultation->getFolderName();

                    $commands[] =  "mklink /J \"$consultationShortcutName\" \"$consultationFolderNameTarget\"";
                }
            }
        }

        $this->executeDeleteCommands($deleteCommands);

        return $commands;
    }

    private function createConsultationShortCutToProjet(Server $server): array
    {
        $consultations = $this->em->getRepository(Consultation::class)->findAll();

        $deleteCommands = [];        
        $commands = [];

        foreach ($server->getFolders() as $folder) {
            $baseFolder = $folder->getPath();

            $devisFolder = $baseFolder . "\\------ DEVIS\\";
            $baseProjetFolderName = $baseFolder . "\\------ PROJET CRM\\";
            $baseConsultationFolder = $baseFolder . "\\------ CONSULTATION CRM\\";

            foreach ($consultations as $consultation) {

                $deleteCommands[] = "for /D %i in (\"{$baseConsultationFolder}{$consultation->getFolderName()}\\*\") do @fsutil reparsepoint query \"%i\" >nul 2>&1 && rmdir /s /q \"%i\"";

                if ($consultation->getProjet()) {
                    $projetFolderNameTarget = $baseProjetFolderName . $consultation->getProjet()->getFolderName();
                    $projetShortcutName = $baseConsultationFolder . $consultation->getFolderName() . "\\" . $consultation->getProjet()->getFolderName();

                    $commands[] =  "mklink /J \"$projetShortcutName\" \"$projetFolderNameTarget\"";
                }

                // $devisFolderNameTarget = $devisFolder . "Devis " . $consultation->getAnneeCreationConsultation();
                // $devisShortcutName = $baseConsultationFolder . $consultation->getFolderName() . "\\Devis " . $consultation->getAnneeCreationConsultation();

                // $commands[] =  "mklink /J \"$devisShortcutName\" \"$devisFolderNameTarget\"";
            }
        }
        
        $this->executeDeleteCommands($deleteCommands);
        return $commands;
    }

    private function executeDeleteCommands(array $commands)
    {
        $server = $this->em->getRepository(Server::class)->findOneBy(['name' => 'Commercial']);
        $ssh = $this->sshService->connexion($server) ;

        foreach ($commands as $command) {
            try {
                $output = $ssh->exec($command);
                $this->folderLogger->info("Executed command: $command, Output: $output");
            } catch (\Exception $e) {
                $this->folderLogger->error("Error executing command: $command, Error: " . $e->getMessage());
            }
        }

        $this->sshService->connexion($server) ;

    }
}
