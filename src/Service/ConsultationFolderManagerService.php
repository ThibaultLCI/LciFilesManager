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
    public function __construct(private LoggerInterface $folderLogger, private EntityManagerInterface $em, private SshService $sshService) {}

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
            $this->executeCommands($server, $commands, "Creation/modification des dossier (" . $parentFolder . " )");
        }
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
        $server = $this->em->getRepository(Server::class)->findOneBy(['isServerCommercial' => true]);

        $this->createProjetShortCutToConsultation($server);
        $this->createConsultationShortCutToProjet($server);

        $this->folderLogger->info("Les raccourcis ont eté créées/modifié");
    }

    private function createProjetShortCutToConsultation(Server $server)
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

        $this->executeCommands($server, $deleteCommands, "Suprression des raccourcis dans les projets");
        $this->executeCommands($server, $commands, "Creation des raccourcis dans les projets vers les consultations");
    }

    private function createConsultationShortCutToProjet(Server $server)
    {
        $consultations = $this->em->getRepository(Consultation::class)->findAll();

        $deleteCommands = [];
        $commands = [];
        $devisCommands = [];

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

                $devisFolderNameTarget = $devisFolder . "Devis " . $consultation->getAnneeCreationConsultation();
                $devisShortcutName = $baseConsultationFolder . $consultation->getFolderName() . "\\Devis " . $consultation->getAnneeCreationConsultation();

                $devisCommands[] =  "mklink /J \"$devisShortcutName\" \"$devisFolderNameTarget\"";
            }
        }

        $this->executeCommands($server, $deleteCommands, "Suprression des raccourcis dans les consultations");
        $this->executeCommands($server, $commands, "Creation des raccourcis dans les consultations vers le projet");
        $this->executeCommands($server, $devisCommands, "Creation des raccourcis dans les consultations vers le dossier devis");
    }

    private function executeCommands(Server $server, array $commands, string $log = null)
    {
        $ssh = $this->sshService->connexion($server);

        $batches = array_chunk($commands, 20);

        foreach ($batches as $batch) {
            $allCommands = implode(' && ', $batch);
            $ssh->exec($allCommands);
        }

        if ($log) {
            $this->folderLogger->info($log);
        }

        $this->sshService->deconnexion($ssh);
    }
}
