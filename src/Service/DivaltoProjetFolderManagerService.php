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

    private function generateCreateFolderCommand(Projet $projet, Folder $folder): string
    {
        $baseFolder = $folder->getPath();
        $projetFolder = $baseFolder . "\\------ PROJET CRM\\";

        $commands = [
            "mkdir \"$projetFolder" . $projet->getFolderName() . "\""
        ];

        return implode(' && ', $commands);
    }

    private function generateEditFolderCommand(Projet $projet, Folder $folder): string
    {
        $baseFolder = $folder->getPath();
        $projetFolder = $baseFolder . "\\------ PROJET CRM\\";

        $newFolderName = $projet->getFolderName();
        $oldFolderName = $projet->getOldFolderName();

        $commands = [];

        $commands[] = "rmdir \"$projetFolder$oldFolderName\"";
        $commands[] = "mkdir \"$projetFolder$newFolderName\"";

        return implode(' && ', $commands);
    }
}
