<?php

namespace App\Service;

use App\Entity\Server;
use phpseclib3\Net\SSH2;
use Psr\Log\LoggerInterface;

class ConsultationFolderManagerService
{
    public function __construct(private LoggerInterface $consultationLogger, private SshService $sshService)
    {
    }

    function createOrUpdateFolderOnServer(Server $server)
    {
        $ssh = $this->sshService->connexion($server);

        $this->initFoldersOnServers($ssh, $server);
    }

    private function initFoldersOnServers(SSH2 $ssh, Server $server): void
    {
        foreach ($server->getFolders() as $folder) {
            $baseFolder = $folder->getPath() . "\\000 - DEV CRM Consultation";
            $projetFolder = $baseFolder . "\\Projets";
            $ConsultationFolder = $baseFolder . "\\Consultation";

            $checkFolderCommand = "if not exist \"$baseFolder\" (echo 0) else (echo 1)";
            $output = $ssh->exec($checkFolderCommand);

            if (trim($output) === '0') {
                $ssh->exec("mkdir \"$projetFolder\"");
                $ssh->exec("mkdir \"$ConsultationFolder\"");
            }
        }

        $this->consultationLogger->info("initialisation des dossiers");
    }
}
