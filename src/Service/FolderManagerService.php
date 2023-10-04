<?php

namespace App\Service;

class FolderManagerService
{
    public function __construct()
    {
    }

    // public function initFolderOnServers(): void
    // {
    //     $userSSH = $this->params->get('user_ssh');

    //     foreach ($this->params->get('servers') as $server) {
    //         $ssh = new SSH2($server['host'], $server['port']);

    //         if (!$ssh->login($userSSH['username'], $userSSH['password'])) {
    //             $this->logger->error('Échec de l\'authentification SSH sur le serveur ' . $server['name']);
    //             continue; // Passer au serveur suivant en cas d'échec d'authentification SSH.
    //         }

    //         $baseFolder = $server['base_directory'] . "\\000 - DEV CRM";
    //         $idFolder = $baseFolder . "\\Id";
    //         $nomUsageFolder = $baseFolder . "\\Nom d'usage";

    //         if (!$this->createFoldersIfNotExist($ssh, [$baseFolder, $idFolder, $nomUsageFolder])) {
    //             $this->logger->error('Échec de création des dossiers sur le serveur ' . $server['name']);
    //         }

    //         $ssh->disconnect();
    //     }
    // }

    // private function createFoldersIfNotExist(SSH2 $ssh, array $folders): bool
    // {
    //     $success = true;

    //     foreach ($folders as $folder) {
    //         $checkFolderCommand = "if not exist \"$folder\" (echo 0) else (echo 1)";
    //         $output = $ssh->exec($checkFolderCommand);

    //         if (trim($output) === '0') {
    //             $ssh->exec("mkdir \"$folder\"");
    //         }
    //     }

    //     return $success;
    // }

    // private function createFolder(Site $newSite): void
    // {
    //     $userSSH = $this->params->get('user_ssh');

    //     foreach ($this->params->get('servers') as $server) {
    //         $baseFolder = $server['base_directory'] . "\\000 - DEV CRM";
    //         $siteIdFolder = $baseFolder . "\\Id\\";
    //         $siteIntituleFolder = $baseFolder . "\\Nom d'usage\\";

    //         $ssh = new SSH2($server['host'], $server['port']);

    //         if (!$ssh->login($userSSH['username'], $userSSH['password'])) {
    //             die('Échec de l\'authentification SSH sur le serveur' . $server['name']);
    //         }

    //         $ssh->exec("mkdir \"$siteIdFolder\"" . $newSite->getIdCrm() . "");

    //         $ssh->exec("echo > \"$siteIdFolder\"" . $newSite->getIdCrm() . "/" . $newSite->getIdCrm() . ".txt");

    //         try {
    //             $linkTarget = $siteIdFolder . $newSite->getIdCrm();
    //             $linkPath = $siteIntituleFolder . $newSite->getIntitule() . " (" . $newSite->getVille() . ")";
    //             $ssh->exec("mklink /J \"$linkPath\" \"$linkTarget\"");
    //         } catch (IOExceptionInterface $exception) {
    //             echo  $exception;
    //         }

    //         $ssh->disconnect();
    //     }
    // }

    // private function editFolder(Site $site, string $oldSiteIntitule): void
    // {
    //     $userSSH = $this->params->get('user_ssh');

    //     foreach ($this->params->get('servers') as $server) {
    //         $baseFolder = $server['base_directory'] . "\\000 - DEV CRM";
    //         $siteIdFolder = $baseFolder . "\\Id\\";
    //         $siteIntituleFolder = $baseFolder . "\\Nom d'usage\\";

    //         $ssh = new SSH2($server['host'], $server['port']);

    //         if (!$ssh->login($userSSH['username'], $userSSH['password'])) {
    //             die('Échec de l\'authentification SSH sur le serveur' . $server['name']);
    //         }

    //         $result = $ssh->exec("rmdir /s /q  \"$siteIntituleFolder\"\"$oldSiteIntitule\"");
    //         echo $result;

    //         $linkTarget = $siteIdFolder . $site->getIdCrm();
    //         $linkPath = $siteIntituleFolder . $site->getIntitule() . " (" . $site->getVille() . ")";
    //         $ssh->exec("mklink /J \"$linkPath\" \"$linkTarget\"");
    //     }
    // }
}
