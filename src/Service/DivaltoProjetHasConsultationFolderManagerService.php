<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Projet;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use phpseclib3\Net\SSH2;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class DivaltoProjetHasConsultationFolderManagerService
{
    function __construct(private EntityManagerInterface $em, private SshService $sshService, private LoggerInterface $projectHasConsultationlogger)
    {
    }

    public function manageShortCut()
    {
        $server = $this->em->getRepository(Server::class)->findOneBy(['name' => 'commercial']);

        $ssh = $this->sshService->connexion($server);

        // $this->createProjetShortCutToConsultation($server, $ssh);
        $this->createConsultationShortCutToProjet($server, $ssh);

        $this->sshService->deconnexion($ssh);
    }

    private function createProjetShortCutToConsultation(Server $server, SSH2 $ssh)
    {
        $projets = $this->em->getRepository(Projet::class)->findAll();

        $commands = [];

        foreach ($server->getFolders() as $folder) {
            $baseFolder = $folder->getPath() . "\\000 - DEV CRM Commercial";

            $baseProjetFolderName = $baseFolder . "\\Projet CRM\\";
            $baseConsultationFolder = $baseFolder . "\\Consultation CRM\\";

            $checkFolderCommand = "if not exist \"$baseFolder\" (echo 0) else (echo 1)";
            $output = $ssh->exec($checkFolderCommand);

            foreach ($projets as $projet) {

                // Supprimer tous les raccourcis existants dans le dossier du projet spécifique
                $deleteAllShortcutsCommand = "for /D %i in (\"{$baseProjetFolderName}{$projet->getFolderName()}\\*\") do rmdir \"%i\"";
                $ssh->exec($deleteAllShortcutsCommand);

                foreach ($projet->getConsultations() as $key => $consultation) {
                    $consultationFolderNameTarget = $baseConsultationFolder . $consultation->getFolderName();
                    $consultationShortcutName = $baseProjetFolderName . $projet->getFolderName() . "\\" . $consultation->getFolderName();

                    $commands[] =  "mklink /J \"$consultationShortcutName\" \"$consultationFolderNameTarget\"";
                }

                foreach ($commands as $command) {
                    $ssh->exec($command);
                }
            }
        }
    }

    private function createConsultationShortCutToProjet(Server $server, SSH2 $ssh)
    {
        $consultations = $this->em->getRepository(Consultation::class)->findAll();

        $commands = [];

        foreach ($server->getFolders() as $folder) {
            $baseFolder = $folder->getPath() . "\\000 - DEV CRM Commercial";

            $baseProjetFolderName = $baseFolder . "\\Projet CRM\\";
            $baseConsultationFolder = $baseFolder . "\\Consultation CRM\\";

            $checkFolderCommand = "if not exist \"$baseFolder\" (echo 0) else (echo 1)";
            $output = $ssh->exec($checkFolderCommand);

            foreach ($consultations as $consultation) {

                // Supprimer tous les raccourcis existants dans le dossier du projet spécifique
                $deleteAllShortcutsCommand = "for /D %i in (\"{$baseConsultationFolder}{$consultation->getFolderName()}\\*\") do rmdir \"%i\"";
                $ssh->exec($deleteAllShortcutsCommand);

                if ($consultation->getProjet()) {
                    $projetFolderNameTarget = $baseProjetFolderName . $consultation->getProjet()->getFolderName();
                    $projetShortcutName = $baseConsultationFolder . $consultation->getFolderName() . "\\" . $consultation->getProjet()->getFolderName();

                    $commands[] =  "mklink /J \"$projetShortcutName\" \"$projetFolderNameTarget\"";
                }

                foreach ($commands as $command) {
                    $ssh->exec($command);
                }
            }
        }
    }
}
