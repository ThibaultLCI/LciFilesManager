<?php

namespace App\Service\ApiDivalto;

use App\Entity\Site;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;

use phpseclib3\Net\SSH2;

class DivaltoSiteService
{
    public function __construct(private EntityManagerInterface $em, private ParameterBagInterface $params, private SiteRepository $siteRepository)
    {
    }

    public function initFolderOnServers()
    {
        $userSSH = $this->params->get('user_ssh');

        foreach ($this->params->get('servers') as $server) {
            $baseFolder = $server['base_directory'] . "\\000 - DEV CRM";
            $idFolder = $baseFolder . "\\Id";
            $nomUsageFolder = $baseFolder . "\\Nom d'usage";

            $ssh = new SSH2($server['host'], $server['port']);

            if (!$ssh->login($userSSH['username'], $userSSH['password'])) {
                die('Échec de l\'authentification SSH sur le serveur' . $server['name']);
            }

            $checkFolderCommand = "if not exist \"$baseFolder\" (echo 0) else (echo 1)";
            $output = $ssh->exec($checkFolderCommand);

            if (trim($output) === '0') {
                $ssh->exec("mkdir \"$baseFolder\"");
                $ssh->exec("mkdir \"$idFolder\"");
                $ssh->exec("mkdir \"$nomUsageFolder\"");
            }

            $ssh->disconnect();
        }
    }

    public function fetchSites(): JsonResponse
    {
        $apiBaseUrl = $this->params->get('divalto_base_url');
        $url = $apiBaseUrl . "?c=B%2BaWlAEI5JEaSGV%2FnPqj7u7sWvTgN7ILpO8ENEDhqf2D1Nl3Bdj589fxKk8dAnx1";

        $pageNumber = 1;
        $maxPageNumber = 0;
        $sites = [];


        do {
            try {
                $params = [
                    "header" =>
                    [
                        "languageCode" => "FR",
                    ],
                    "action" =>
                    [
                        "verb" => "LIST", // GET, PUT, DELETE, LIST, DEFINITION
                        "parameters" =>
                        [
                            "pageNumber" => $pageNumber,
                            "listType" => "simple",
                            "filters" => [
                                "customerFamily" => "SITE"
                            ],
                            "orderBy" => "ASC",
                        ],
                    ],
                ];

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_HTTPHEADER,     array('Content-Type: JSON'));
                curl_setopt($curl, CURLINFO_HEADER_OUT, true);

                $result = curl_exec($curl);

                curl_close($curl);

                $result = json_decode($result, true);

                $maxPageNumber = $result["result"]["response"]["maxPageNumber"];
                $data = $result["result"]["response"]["data"];

                foreach ($data as $site) {
                    array_push($sites, $site);
                }
            } catch (\Throwable $th) {
                throw $th;
            }
            $pageNumber++;
        } while ($pageNumber - 1 < $maxPageNumber);

        return  $this->checkDatabaseSite($sites);
    }

    public function clearSite()
    {
        $sites = $this->siteRepository->findAll();

        try {
            foreach ($sites as $site) {
                $this->em->remove($site);
            }

            $this->em->flush();

            return new JsonResponse("clear Site");
        } catch (IOExceptionInterface $exception) {
            echo  $exception;
        }
    }

    private function checkDatabaseSite($crmSites): JsonResponse
    {
        $nbNewSites = 0;
        $nbUpdatedSites = 0;

        foreach ($crmSites as $crmSite) {
            $site = $this->siteRepository->findOneBy([
                'idCrm' => $crmSite["customer"]["codecustomer"]
            ]);

            $newSite = new Site();
            $newSite->setIdCrm($crmSite["customer"]["codecustomer"])
                ->setIntitule($crmSite["customer"]["name"])
                ->setAdresse($crmSite["customer"]["address1"])
                ->setCodePostal($crmSite["customer"]["postalCode"])
                ->setVille($crmSite["customer"]["city"]);

            if (!$site) {
                $this->em->persist($newSite);
                $this->createFolder($newSite);
                $nbNewSites++;
            } else {
                $hasUpdate = false;
                $oldSiteIntitule = null;

                if ($site->getIntitule() != $newSite->getIntitule()) {
                    $oldSiteIntitule = $site->getIntitule() . " (" . $newSite->getVille() . ")";
                    $site->setIntitule($newSite->getIntitule());
                    $this->editFolder($site, $oldSiteIntitule);
                    $hasUpdate = true;
                }

                if ($site->getAdresse() != $newSite->getAdresse()) {
                    $site->setAdresse($newSite->getAdresse());
                    $hasUpdate = true;
                }

                if ($site->getCodePostal() != $newSite->getCodePostal()) {
                    $site->setCodePostal($newSite->getCodePostal());
                    $hasUpdate = true;
                }

                if ($site->getVille() != $newSite->getVille()) {
                    $site->setVille($newSite->getVille());
                    $hasUpdate = true;
                }

                if ($hasUpdate) {
                    $nbUpdatedSites++;
                }
            }
        }
        $this->em->flush();

        return new JsonResponse($nbNewSites . " site(s) ajouté, " . $nbUpdatedSites . " site(s) mis a jour");
    }

    private function createFolder(Site $newSite): void
    {
        $userSSH = $this->params->get('user_ssh');

        foreach ($this->params->get('servers') as $server) {
            $baseFolder = $server['base_directory'] . "\\000 - DEV CRM";
            $siteIdFolder = $baseFolder . "\\Id\\";
            $siteIntituleFolder = $baseFolder . "\\Nom d'usage\\";

            $ssh = new SSH2($server['host'], $server['port']);

            if (!$ssh->login($userSSH['username'], $userSSH['password'])) {
                die('Échec de l\'authentification SSH sur le serveur' . $server['name']);
            }

            $ssh->exec("mkdir \"$siteIdFolder\"" . $newSite->getIdCrm() . "");

            $ssh->exec("echo > \"$siteIdFolder\"" . $newSite->getIdCrm() . "/" . $newSite->getIdCrm() . ".txt");

            try {
                $linkTarget = $siteIdFolder . $newSite->getIdCrm();
                $linkPath = $siteIntituleFolder . $newSite->getIntitule() . " (" . $newSite->getVille() . ")";
                $ssh->exec("mklink /J \"$linkPath\" \"$linkTarget\"");
            } catch (IOExceptionInterface $exception) {
                echo  $exception;
            }

            $ssh->disconnect();
        }
    }

    private function editFolder(Site $site, string $oldSiteIntitule): void
    {
        $userSSH = $this->params->get('user_ssh');

        foreach ($this->params->get('servers') as $server) {
            $baseFolder = $server['base_directory'] . "\\000 - DEV CRM";
            $siteIdFolder = $baseFolder . "\\Id\\";
            $siteIntituleFolder = $baseFolder . "\\Nom d'usage\\";

            $ssh = new SSH2($server['host'], $server['port']);

            if (!$ssh->login($userSSH['username'], $userSSH['password'])) {
                die('Échec de l\'authentification SSH sur le serveur' . $server['name']);
            }

            $result = $ssh->exec("rmdir /s /q  \"$siteIntituleFolder\"\"$oldSiteIntitule\"");
            echo $result;

            $linkTarget = $siteIdFolder . $site->getIdCrm();
            $linkPath = $siteIntituleFolder . $site->getIntitule() . " (" . $site->getVille() . ")";
            $ssh->exec("mklink /J \"$linkPath\" \"$linkTarget\"");
        }
    }
}
