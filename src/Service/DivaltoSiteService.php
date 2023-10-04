<?php

namespace App\Service;

use App\Entity\Server;
use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DivaltoSiteService
{
    public function __construct(private EntityManagerInterface $em, private ParameterBagInterface $params)
    {
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
        $siteRepository = $this->em->getRepository(Site::class);
        $sites = $siteRepository->findAll();

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
        $serverRepository = $this->em->getRepository(Server::class);
        $folderRepository = $this->em->getRepository(Folder::class);
        $siteRepository = $this->em->getRepository(Site::class);

        foreach ($serverRepository->findAll() as $server) {
            foreach ($folderRepository->findBy(['site' => $server->getSite()]) as $folder) {
                foreach ($crmSites as $crmSite) {
                    $site = $siteRepository->findOneBy([
                        'idCrm' => $crmSite["customer"]["codecustomer"],
                        'server' => $server,
                        'folder' => $folder
                    ]);
                }
            }
        }



        return new JsonResponse("Hello");
    }

    // private function checkDatabaseSite($crmSites): JsonResponse
    // {
    //     $nbNewSites = 0;
    //     $nbUpdatedSites = 0;

    //     $entityManager = $this->em;

    //     try {
    //         foreach ($crmSites as $crmSite) {
    //             $site = $this->siteRepository->findOneBy([
    //                 'idCrm' => $crmSite["customer"]["codecustomer"]
    //             ]);

    //             $newSite = new Site();
    //             $newSite->setIdCrm($crmSite["customer"]["codecustomer"])
    //                 ->setIntitule($crmSite["customer"]["name"])
    //                 ->setVille($crmSite["customer"]["city"]);

    //             if (!$site) {
    //                 $entityManager->persist($newSite);
    //                 $this->createFolder($newSite);
    //                 $nbNewSites++;
    //             } else {
    //                 $hasUpdate = false;
    //                 $oldSiteIntitule = null;

    //                 if ($site->getIntitule() != $newSite->getIntitule()) {
    //                     $oldSiteIntitule = $site->getIntitule() . " (" . $newSite->getVille() . ")";
    //                     $site->setIntitule($newSite->getIntitule());
    //                     $this->editFolder($site, $oldSiteIntitule);
    //                     $hasUpdate = true;
    //                 }

    //                 if ($site->getVille() != $newSite->getVille()) {
    //                     $site->setVille($newSite->getVille());
    //                     $hasUpdate = true;
    //                 }

    //                 if ($hasUpdate) {
    //                     $nbUpdatedSites++;
    //                 }
    //             }
    //         }

    //         $entityManager->flush();

    //         return new JsonResponse($nbNewSites . " site(s) ajouté, " . $nbUpdatedSites . " site(s) mis a jour");
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }
}
