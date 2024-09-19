<?php

namespace App\Service;

use App\Entity\Server;
use phpseclib3\Net\SSH2;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class SiteFolderManagerService
{
    public function __construct(private SshService $sshService, private LoggerInterface $logger) {}

    function createOrUpdateFolderOnServer(array $siteFolderToCreate, array $siteFolderToUpdate, Server $server)
    {
        if (count($siteFolderToCreate) != 0 || count($siteFolderToUpdate) != 0) {
            $start = microtime(true);

            $ssh = $this->sshService->connexion($server);

            $commands = [];

            foreach ($siteFolderToCreate as $siteFolder) {
                $commands[] = $this->generateCreateFolderCommand($siteFolder);
            }

            foreach ($siteFolderToUpdate as $key => $siteFolder) {
                $oldSiteIntitule = $siteFolder["Site"]->getOldIntitule();
                $commands[] = $this->generateEditFolderCommand($siteFolder, $oldSiteIntitule);
            }

            $batches = array_chunk($commands, 20);

            foreach ($batches as $batch) {
                $allCommands = implode(' && ', $batch);
                $ssh->exec($allCommands);
            }

            $this->sshService->deconnexion($ssh);

            $end = microtime(true) - $start;
            $this->logger->info("temps creation/modification de dossier : " . $end . "\n");
        }
    }

    private function generateCreateFolderCommand(array $siteFolder): string
    {
        $site = $siteFolder["Site"];
        $folder = $siteFolder["Folder"];

        $baseFolder = $folder->getPath();
        $siteIdFolder = $baseFolder . "\\Id\\";
        $siteIntituleFolder = $baseFolder . "\\Nom d'usage\\";

        $commands = [
            'powershell -Command "New-Item -Path \'' . str_replace("'", "''", $siteIdFolder . $site->getIdCrm()) . '\' -ItemType Directory"',
            'powershell -Command "New-Item -Path \'' . str_replace("'", "''", $siteIdFolder . $site->getIdCrm() . "\\" . $site->getIdCrm() . '.txt') . '\' -ItemType File"'
        ];

        try {
            $linkTarget = $siteIdFolder . $site->getIdCrm();
            $linkPath = $siteIntituleFolder . $site->getIntitule()  . ".lnk";

            // $commands[] = "mklink /J \"$linkPath\" \"$linkTarget\"";

            $commands[] = 'powershell -Command "$s = (New-Object -COM WScript.Shell).CreateShortcut(\''
                . str_replace("'", "''", $linkPath)
                . '\'); $s.TargetPath = \''
                . str_replace("'", "''", $linkTarget)
                . '\'; $s.Save()"';

            $site->addFolder($folder);
        } catch (IOExceptionInterface $exception) {
            $this->logger->info($exception);
        }

        return implode(' && ', $commands);
    }

    private function generateEditFolderCommand(array $siteFolder, string $oldSiteIntitule): string
    {
        $site = $siteFolder["Site"];
        $folder = $siteFolder["Folder"];

        $baseFolder = $folder->getPath();
        $siteIdFolder = $baseFolder . "\\Id\\";
        $siteIntituleFolder = $baseFolder . "\\Nom d'usage\\";

        $commands = [];

        $commands[] = 'powershell -Command "Remove-Item -Path \''
            . str_replace("'", "''", $siteIntituleFolder . $oldSiteIntitule . ".lnk")
            . '\' -Force -ErrorAction SilentlyContinue"';

        $linkTarget = $siteIdFolder . $site->getIdCrm();
        $linkPath = $siteIntituleFolder . $site->getIntitule() . ".lnk";
        $commands[] = 'powershell -Command "$s = (New-Object -COM WScript.Shell).CreateShortcut(\''
            . str_replace("'", "''", $linkPath)
            . '\'); $s.TargetPath = \''
            . str_replace("'", "''", $linkTarget)
            . '\'; $s.Save()"';

        return implode(' && ', $commands);
    }
}
