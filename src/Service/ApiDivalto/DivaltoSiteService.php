<?php

namespace App\Service\ApiDivalto;

use App\Entity\Site;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;

class DivaltoSiteService
{
    public function __construct(private EntityManagerInterface $em, private ParameterBagInterface $params, private SiteRepository $siteRepository)
    {
    }

    public function fetchSites(): JsonResponse
    {
        $apiBaseUrl = $this->params->get('divalto_base_url');
        $url = $apiBaseUrl . "?c=B%2BaWlAEI5JEaSGV%2FnPqj7u7sWvTgN7ILpO8ENEDhqf2odk2H5o%2FlRIOHC95yyqq7";

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
        $filesystem = new Filesystem();
        $sites = $this->siteRepository->findAll();

        try {
            foreach ($this->params->get('servers') as $server) {
                foreach ($server as $key => $folderpath) {
                    if ($key === "local_app_directory") {
                        $filesystem->remove($folderpath);
                    }
                }
            }

            foreach ($sites as $site) {
                $this->em->remove($site);
            }

            // $this->em->flush();

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
        // $this->em->flush();

        return new JsonResponse($nbNewSites . " site(s) ajouté, " . $nbUpdatedSites . " site(s) mis a jour");
    }

    private function createFolder(Site $newSite): void
    {
        $filesystem = new Filesystem();

        foreach ($this->params->get('servers') as $server) {
            $serverBaseDirectory = $server['directory'];
            $siteIdFolder = $serverBaseDirectory . "/Id/";
            $siteIntituleFolder = $serverBaseDirectory . "/Nom d'usage/";

            $filesystem->mkdir($siteIdFolder . $newSite->getIdCrm());
            $filesystem->touch($siteIdFolder . "" . $newSite->getIdCrm() . "/" . $newSite->getIdCrm() . "test.txt");

            try {
                $filesystem->symlink($siteIdFolder . "" . $newSite->getIdCrm(), $siteIntituleFolder . "" . $newSite->getIntitule() . " (" . $newSite->getVille() . ")");
                $filesystem->symlink($siteIdFolder . "" . $newSite->getIdCrm(),  $server["local_app_directory"] . "/Nom d'usage/" . "" . $newSite->getIntitule() . " (" . $newSite->getVille() . ")");
            } catch (IOExceptionInterface $exception) {
                echo  $exception;
            }
        }
    }

    private function editFolder(Site $site, string $oldSiteIntitule): void
    {
        $filesystem = new Filesystem();

        foreach ($this->params->get('servers') as $server) {

            $filesystem->remove($server['directory'] . "/Nom d'usage/" . $oldSiteIntitule);
            $filesystem->remove($server['local_app_directory'] . "/Nom d'usage/" . $oldSiteIntitule);

            $filesystem->symlink($server['directory'] . "/Id/" . $site->getIdCrm(), $server['directory'] . "/Nom d'usage/" . $site->getIntitule() . " (" . $site->getVille() . ")");
            $filesystem->symlink($server['directory'] . "/Id/" . $site->getIdCrm(), $server['local_app_directory'] . "/Nom d'usage/" . "" . $site->getIntitule() . " (" . $site->getVille() . ")");
        }
    }
}
