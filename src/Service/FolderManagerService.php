<?php

namespace App\Service;

use App\Entity\Server;
use App\Entity\Site;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class FolderManagerService
{
    public function __construct(private SshService $sshService)
    {
    }

    function createOrUpdateFolderOnServer(array $siteFolderToCreate, array $siteFolderToUpdate, Server $server)
    {
        if (count($siteFolderToCreate) != 0 || count($siteFolderToUpdate) != 0) {
            $start = microtime(true);

            $ssh = $this->sshService->connexion($server);

            $this->initFoldersOnServers($ssh, $server);

            foreach ($siteFolderToCreate as $siteFolder) {
                $this->createFolder($ssh, $siteFolder);
            }

            foreach ($siteFolderToUpdate as $siteFolder) {
                $this->editFolder($ssh, $siteFolder);
            }

            $this->sshService->deconnexion($ssh);

            $end = microtime(true) - $start;
            echo "temp creation/modification de dossier : " . $end ."\n";
        }
    }

    private function initFoldersOnServers(SSH2 $ssh, Server $server): void
    {
        foreach ($server->getFolders() as $folder) {
            $baseFolder = $folder->getPath() . "\\000 - DEV CRM";
            $idFolder = $baseFolder . "\\Id";
            $nomUsageFolder = $baseFolder . "\\Nom d'usage";

            $checkFolderCommand = "if not exist \"$baseFolder\" (echo 0) else (echo 1)";
            $output = $ssh->exec($checkFolderCommand);

            if (trim($output) === '0') {
                $ssh->exec("mkdir \"$baseFolder\"");
                $ssh->exec("mkdir \"$idFolder\"");
                $ssh->exec("mkdir \"$nomUsageFolder\"");
            }
        }
    }

    private function createFolder(SSH2 $ssh, array $siteFolder): void
    {
        $site = $siteFolder["Site"];
        $folder = $siteFolder["Folder"];

        $baseFolder = $folder->getPath() . "\\000 - DEV CRM";
        $siteIdFolder = $baseFolder . "\\Id\\";
        $siteIntituleFolder = $baseFolder . "\\Nom d'usage\\";

        $ssh->exec("mkdir \"$siteIdFolder\"" . $site->getIdCrm() . "");

        $ssh->exec("echo > \"$siteIdFolder\"" . $site->getIdCrm() . "/" . $site->getIdCrm() . ".txt");

        try {
            $linkTarget = $siteIdFolder . $site->getIdCrm();
            $linkPath = $siteIntituleFolder . $site->getIntitule();
            $ssh->exec("mklink /J \"$linkPath\" \"$linkTarget\"");
            $site->addFolder($folder);
        } catch (IOExceptionInterface $exception) {
            echo  $exception;
        }
    }

    private function editFolder(SSH2 $ssh, array $siteFolder): void
    {
        $site = $siteFolder["Site"];
        $oldSiteIntitule = $site->getOldIntitule();
        $folder = $siteFolder["Folder"];

        $baseFolder = $folder->getPath() . "\\000 - DEV CRM";
        $siteIdFolder = $baseFolder . "\\Id\\";
        $siteIntituleFolder = $baseFolder . "\\Nom d'usage\\";

        $ssh->exec("rmdir /s /q  \"$siteIntituleFolder\"\"$oldSiteIntitule\"");

        $linkTarget = $siteIdFolder . $site->getIdCrm();
        $linkPath = $siteIntituleFolder . $site->getIntitule();
        $ssh->exec("mklink /J \"$linkPath\" \"$linkTarget\"");
    }
}
