<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Folder;
use App\Entity\Projet;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use phpseclib3\Net\SSH2;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class ConsultationFolderManagerService
{
    public function __construct(private LoggerInterface $folderLogger, private EntityManagerInterface $em, private SshService $sshService) {}

    function createOrUpdateFolderOnServer(array $foldersToCreate, array $foldersToUpdate, Server $server, string $parentFolder)
    {
        if (count($foldersToCreate) != 0 || count($foldersToUpdate) != 0) {
            $commands = [];

            foreach ($foldersToCreate as $folderToCreate) {
                foreach ($server->getFolders() as $folder) {
                    foreach ($this->generateCreateFolderCommand($folderToCreate, $folder, $parentFolder) as $command) {
                        array_push($commands, $command);
                    }
                }
            }

            foreach ($foldersToUpdate as $folderToUpdate) {
                foreach ($server->getFolders() as $folder) {
                    foreach ($this->generateEditFolderCommand($folderToUpdate, $folder, $parentFolder) as $command) {
                        array_push($commands, $command);
                    }
                }
            }

            $this->executeCommands($server, $commands, "Creation/modification des dossier (" . $parentFolder . " )");
        }
    }

    private function generateCreateFolderCommand(Consultation|Projet $entity, Folder $folder, string $parentFolder): array
    {
        $baseFolder = $folder->getPath() . $parentFolder;
        $idFolder = $baseFolder . "Id\\";
        $intituleFolder = $baseFolder . "Nom d'usage\\";

        $textFile = $parentFolder === "\\------ PROJET CRM\\" ? "Projet " : "Consultation ";

        $commands = [
            'powershell -Command "New-Item -Path \'' . str_replace("'", "''", $idFolder . $entity->getIdCrm()) . '\' -ItemType Directory"',
            'powershell -Command "New-Item -Path \'' . str_replace("'", "''", $idFolder . $entity->getIdCrm()) . '\\' . str_replace("'", "''", $textFile . $entity->getIdCrm()) . '.txt\' -ItemType File"'
        ];

        try {
            $linkTarget = $idFolder . $entity->getIdCrm();
            $linkPath = $intituleFolder . $entity->getFolderName() . ".lnk";

            $commands[] = 'powershell -Command "$s = (New-Object -COM WScript.Shell).CreateShortcut(\'' . str_replace("'", "''", $linkPath) . '\'); $s.TargetPath = \'' . str_replace("'", "''", $linkTarget) . '\'; $s.Save()"';
        } catch (\Exception $exception) {
            $this->folderLogger->error("Erreur lors de la génération du lien symbolique : " . $exception->getMessage());
        }

        return $commands;
    }

    private function generateEditFolderCommand(Consultation|Projet $entity, Folder $folder, string $parentFolder): array
    {
        $baseFolder = $folder->getPath() . $parentFolder;
        $idFolder = $baseFolder . "Id\\";
        $intituleFolder = $baseFolder . "Nom d'usage\\";

        $commands = [];

        try {
            $oldFolderName = $entity->getOldFolderName();
            $commands[] = 'powershell -Command "Remove-Item -Path \'' . str_replace("'", "''", $intituleFolder . $oldFolderName . '.lnk') . '\'"';
        } catch (\Exception $exception) {
            $this->folderLogger->error("Erreur lors de la suppression de l'ancien lien symbolique : " . $exception->getMessage());
        }

        try {
            $linkTarget = $idFolder . $entity->getIdCrm();
            $linkPath =  $intituleFolder . $entity->getFolderName() . ".lnk";
            $commands[] = 'powershell -Command "$s = (New-Object -COM WScript.Shell).CreateShortcut(\'' . str_replace("'", "''", $linkPath) . '\'); $s.TargetPath = \'' . str_replace("'", "''", $linkTarget) . '\'; $s.Save()"';
        } catch (\Exception $exception) {
            $this->folderLogger->error("Erreur lors de la création du lien symbolique : " . $exception->getMessage());
        }

        return $commands;
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

            $baseProjetFolderName = $baseFolder . "\------ PROJET CRM\\";
            $baseConsultationFolder = $baseFolder . "\------ CONSULTATION CRM\\";

            foreach ($projets as $projet) {

                $deleteCommands[] = 'powershell -Command "Get-ChildItem -Path \'' . $baseProjetFolderName . "\Id\\" . $projet->getIdCrm() . '\' -Filter *.lnk | Remove-Item -Force"';

                foreach ($projet->getConsultations() as $key => $consultation) {
                    $consultationFolderNameTarget = $baseConsultationFolder . "Id\\" . $consultation->getIdCrm();
                    $consultationShortcutName = $baseProjetFolderName . "Id\\" . $projet->getIdCrm() . "\\" . $consultation->getFolderName() . ".lnk";

                    $commands[] = 'powershell -Command "$s = (New-Object -COM WScript.Shell).CreateShortcut(\'' . str_replace("'", "''", $consultationShortcutName) . '\'); $s.TargetPath = \'' . str_replace("'", "''", $consultationFolderNameTarget) . '\'; $s.Save()"';
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

            $devisFolder = $baseFolder . "\------ DEVIS\\";
            $baseProjetFolderName = $baseFolder . "\------ PROJET CRM\\";
            $baseConsultationFolder = $baseFolder . "\------ CONSULTATION CRM\\";

            foreach ($consultations as $consultation) {

                $deleteCommands[] = 'powershell -Command "Get-ChildItem -Path \'' . $baseConsultationFolder . "\Id\\" . $consultation->getIdCrm() . '\' -Filter *.lnk | Remove-Item -Force"';

                if ($consultation->getProjet()) {
                    $projetFolderNameTarget = $baseProjetFolderName . "Id\\" . $consultation->getProjet()->getIdCrm();
                    $projetShortcutName = $baseConsultationFolder . "Id\\" . $consultation->getIdCrm() . "\\" . $consultation->getProjet()->getFolderName() . ".lnk";

                    $commands[] = 'powershell -Command "$s = (New-Object -COM WScript.Shell).CreateShortcut(\'' . str_replace("'", "''", $projetShortcutName) . '\'); $s.TargetPath = \'' . str_replace("'", "''", $projetFolderNameTarget) . '\'; $s.Save()"';
                }

                $devisFolderNameTarget = $devisFolder . "Devis " . $consultation->getAnneeCreationConsultation();
                $devisShortcutName = $baseConsultationFolder . "Id\\" . $consultation->getIdCrm() . "\\Devis " . $consultation->getAnneeCreationConsultation() . ".lnk";

                $devisCommands[] = 'powershell -Command "$s = (New-Object -COM WScript.Shell).CreateShortcut(\'' . str_replace("'", "''", $devisShortcutName) . '\'); $s.TargetPath = \'' . str_replace("'", "''", $devisFolderNameTarget) . '\'; $s.Save()"';
            }
        }

        $this->executeCommands($server, $deleteCommands, "Suprression des raccourcis dans les consultations");
        $this->executeCommands($server, $commands, "Creation des raccourcis dans les consultations vers le projet");
        $this->executeCommands($server, $devisCommands, "Creation des raccourcis dans les consultations vers le dossier devis");
    }

    private function executeCommands(Server $server, array $commands, string $log = null)
    {
        $ssh = $this->sshService->connexion($server);

        foreach ($commands as $command) {
            $output = $ssh->exec($command);
            $errorOutput = $ssh->getStdError();

            if (!empty($errorOutput)) {
                $this->folderLogger->error("Erreur lors de l'exécution de la commande : $command");
                $this->folderLogger->error("Message d'erreur : $errorOutput");

                return;
            }
            $this->folderLogger->info($command);
        }

        if ($log) {
            $this->folderLogger->info($log);
        }

        $this->sshService->deconnexion($ssh);
    }
}
