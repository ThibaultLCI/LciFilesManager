<?php

namespace App\Service;

use App\Entity\Server;
use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DivaltoSiteService
{
    public function __construct(private EntityManagerInterface $em, private ParameterBagInterface $params, private DivaltoFolderManagerService $divaltoFolderManagerService, private SshService $sshService, private LoggerInterface $logger)
    {
    }

    public function fetchSites(): JsonResponse
    {
        $start = microtime(true);

        $url = $this->params->get('divalto_customer_url');

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

        $end = microtime(true) - $start;
        $this->logger->info("temps Call Api : " . $end . "\n");

        return  $this->checkDatabaseSite($sites);
        // return new JsonResponse("Call Api");
    }

    public function clearSites(): JsonResponse
    {
        $siteRepository = $this->em->getRepository(Site::class);
        $sites = $siteRepository->findAll();

        foreach ($sites as $site) {
            $this->em->remove($site);
        }
        $this->em->flush();


        return new JsonResponse("Site Clear");
    }

    private function checkDatabaseSite($crmSites): JsonResponse
    {
        $serverRepository = $this->em->getRepository(Server::class);
        $siteRepository = $this->em->getRepository(Site::class);

        $infoSite = $this->addOrUpdateSites($crmSites);

        foreach ($serverRepository->findAll() as $server) {
            $siteFolderToCreate = [];
            $siteFolderToUpdate = [];

            foreach ($server->getFolders() as $folder) {
                foreach ($crmSites as $crmSite) {

                    $site = $siteRepository->findOneBy([
                        'idCrm' => $crmSite["customer"]["codecustomer"],
                    ]);

                    if (!$site->getFolders()->contains($folder)) {
                        array_push($siteFolderToCreate, ["Site" => $site, "Folder" => $folder]);
                    } elseif ($site && $site->getOldIntitule()) {
                        array_push($siteFolderToUpdate, ["Site" => $site, "Folder" => $folder]);
                    }
                }
            }
            $this->divaltoFolderManagerService->createOrUpdateFolderOnServer($siteFolderToCreate, $siteFolderToUpdate, $server);
        }


        $this->clearOldIntitule();

        $this->em->flush();

        $infoSite = json_decode($infoSite->getContent());

        $this->logger->info($this->sshService->getNbOuvertureSsh());

        return new JsonResponse($infoSite);
    }

    private function addOrUpdateSites(array $crmSites): JsonResponse
    {
        $siteRepository = $this->em->getRepository(Site::class);
        $nbNewSites = 0;
        $nbUpdatedSites = 0;


        foreach ($crmSites as $crmSite) {
            $newSite = new Site();
            $newSite->setIdCrm($crmSite["customer"]["codecustomer"])
                ->setIntitule($crmSite["customer"]["name"] . " (" . $crmSite["customer"]["city"] . ")")
                ->setVille($crmSite["customer"]["city"]);

            $site = $siteRepository->findOneBy([
                'idCrm' => $crmSite["customer"]["codecustomer"],
            ]);

            if (!$site) {
                $this->em->persist($newSite);
                $nbNewSites++;
            } else {
                $hasUpdate = false;
                $oldSiteIntitule = $site->getIntitule();

                if ($site->getIntitule() != $newSite->getIntitule()) {
                    $site->setIntitule($newSite->getIntitule());
                    $hasUpdate = true;
                }

                if ($site->getVille() != $newSite->getVille()) {
                    $site->setVille($newSite->getVille());
                    $hasUpdate = true;
                }

                if ($hasUpdate) {
                    $site->setOldIntitule($oldSiteIntitule);
                    $nbUpdatedSites++;
                }
            }
        }

        $this->em->flush();

        return new JsonResponse($nbNewSites . " site(s) ajouté, " . $nbUpdatedSites . " site(s) mis a jour");
    }

    private function clearOldIntitule(): void
    {
        $siteRepository = $this->em->getRepository(Site::class);

        $sites = $siteRepository->findSitesWithOldIntile();

        foreach ($sites as $site) {
            $site->setOldIntitule(null);
        }
    }

    /**
     * TODO
     * fullPathFolder => Se mettre d'accord sur un architecture
     * Actuellement cela genere un dossier 000 - DEV CRM qui contient un dossier Id et un dossier Nom d'usage
     * le fullPathFolder est donc 000 - DEV CRM/Nom d'usage/{nomDuSite}
     * nextcloudurl = {nomDeDomaine}/index.php/apps/files/?dir=/{serverNameOnNextCloud}/{fullPathFolder}
     * 
     * Voir ou/quoi envoyer a divalto quand cela sera disponible de leut coter
     */
    private function sendNextcloudUrl(array $newSites, array $editSites, $server): void
    {
        $urlsToSend = [];

        foreach ($newSites as $newSite) {
            $newUrl = "10.1.10.37/index.php/apps/files/?dir=/" . $server->getName() . "/000 - DEV CRM/Nom d'usage/" . $newSite["Site"]->getIntitule();
            array_push($urlsToSend, $newUrl);
        }

        //todo ou/quoi envoyer a divalto selon le server
    }
}
