<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Folder;
use App\Entity\Server;
use phpseclib3\Net\SSH2;
use Psr\Log\LoggerInterface;

class DivaltoConsultationFolderManagerService
{
    public function __construct(private LoggerInterface $consultationLogger, private SshService $sshService)
    {
    }

    function createOrUpdateFolderOnServer(array $consultationFolderToCreate, array $consultationFolderToUpdate, Server $server)
    {
        if (count($consultationFolderToCreate) != 0 || count($consultationFolderToUpdate) != 0) {

            $ssh = $this->sshService->connexion($server);

            $commands = [];

            foreach ($consultationFolderToCreate as $consultationFolder) {
                foreach ($server->getFolders() as $folder) {
                    $commands[] = $this->generateCreateFolderCommand($consultationFolder, $folder);
                }
            }

            foreach ($consultationFolderToUpdate as $consultationFolder) {
                foreach ($server->getFolders() as $folder) {
                    $commands[] = $this->generateEditFolderCommand($consultationFolder, $folder);
                }
            }

            $batches = array_chunk($commands, 20);

            foreach ($batches as $batch) {
                $allCommands = implode(' && ', $batch);
                $return = $ssh->exec($allCommands);

                if ($return) {
                    $this->consultationLogger->info($return);
                }

            }

            $this->sshService->deconnexion($ssh);
        }
    }

    private function generateCreateFolderCommand(Consultation $consultation, Folder $folder): string
    {
        $baseFolder = $folder->getPath();
        $consultationFolder = $baseFolder . "\\\------ CONSULTATION CRM\\";

        $commands = [
            "mkdir \"$consultationFolder" . $consultation->getFolderName() . "\""
        ];

        return implode(' && ', $commands);
    }

    private function generateEditFolderCommand(Consultation $consultation, Folder $folder): string
    {
        $baseFolder = $folder->getPath();
        $consultationFolder = $baseFolder . "\\\------ CONSULTATION CRM\\";

        $newFolderName = $consultation->getFolderName();
        $oldFolderName = $consultation->getOldFolderName();

        $commands = [];

        $commands[] = "rmdir \"$consultationFolder$oldFolderName\"";
        $commands[] = "mkdir \"$consultationFolder$newFolderName\"";

        return implode(' && ', $commands);
    }
}
