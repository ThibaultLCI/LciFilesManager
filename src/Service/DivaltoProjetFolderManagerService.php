<?php

namespace App\Service;

use App\Entity\Projet;
use App\Entity\Folder;
use App\Entity\Server;
use phpseclib3\Net\SSH2;
use Psr\Log\LoggerInterface;

class DivaltoProjetFolderManagerService
{
    public function __construct(private LoggerInterface $projetLogger, private SshService $sshService)
    {
    }

    function createOrUpdateFolderOnServer(array $projetFolderToCreate, array $projetFolderToUpdate, Server $server)
    {
        if (count($projetFolderToCreate) != 0 || count($projetFolderToUpdate) != 0) {

            $ssh = $this->sshService->connexion($server);

            $this->initFoldersOnServers($ssh, $server);

            $commands = [];

            foreach ($projetFolderToCreate as $projetFolder) {
                foreach ($server->getFolders() as $folder) {
                    $commands[] = $this->generateCreateFolderCommand($projetFolder, $folder);
                }
            }

            foreach ($projetFolderToUpdate as $projetFolder) {
                foreach ($server->getFolders() as $folder) {
                    $commands[] = $this->generateEditFolderCommand($projetFolder, $folder);
                }
            }

            $batches = array_chunk($commands, 20);

            foreach ($batches as $batch) {
                $allCommands = implode(' && ', $batch);
                $return = $ssh->exec($allCommands);

                if ($return) {
                    $this->projetLogger->info($return);
                }

            }

            $this->sshService->deconnexion($ssh);
        }
    }

    private function initFoldersOnServers(SSH2 $ssh, Server $server): void
    {
        foreach ($server->getFolders() as $folder) {
            $baseFolder = $folder->getPath() . "\\000 - DEV CRM Commercial";
            $projetFolder = $baseFolder . "\\Projet CRM";
            $ProjetFolder = $baseFolder . "\\Projet CRM";

            $checkFolderCommand = "if not exist \"$baseFolder\" (echo 0) else (echo 1)";
            $output = $ssh->exec($checkFolderCommand);

            if (trim($output) === '0') {
                $ssh->exec("mkdir \"$projetFolder\"");
                $ssh->exec("mkdir \"$ProjetFolder\"");
                $this->projetLogger->info("initialisation du dossier commercial");
            }
        }
    }

    private function generateCreateFolderCommand(Projet $projet, Folder $folder): string
    {
        $baseFolder = $folder->getPath() . "\\000 - DEV CRM Commercial";
        $projetFolder = $baseFolder . "\\Projet CRM\\";

        $commands = [
            "mkdir \"$projetFolder" . $projet->getFolderName() . "\""
        ];

        return implode(' && ', $commands);
    }

    private function generateEditFolderCommand(Projet $projet, Folder $folder): string
    {
        $this->projetLogger->info('here');
        $baseFolder = $folder->getPath() . "\\000 - DEV CRM Commercial";
        $projetFolder = $baseFolder . "\\Projet CRM\\";

        $newFolderName = $projet->getFolderName();
        $oldFolderName = $projet->getOldFolderName();

        $commands = [];

        $commands[] = "rmdir \"$projetFolder$oldFolderName\"";
        $commands[] = "mkdir \"$projetFolder$newFolderName\"";

        return implode(' && ', $commands);
    }
}
