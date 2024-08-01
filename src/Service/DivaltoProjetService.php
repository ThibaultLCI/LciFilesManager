<?php

namespace App\Service;

use App\Entity\Projet;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DivaltoProjetService
{
    public function __construct(private ParameterBagInterface $params, private LoggerInterface $projetLogger, private EntityManagerInterface $em, private DivaltoTierService $divaltoTierService, private DivaltoProjetFolderManagerService $divaltoProjetFolderManagerService)
    {
    }

    public function fetchProjets()
    {
        $url = $this->params->get('divalto_projetheader_url');

        $pageNumber = 1;
        $maxPageNumber = 0;
        $projets = [];

        do {
            try {
                $params = [
                    "header" =>
                    [
                        "languageCode" => "FR",
                    ],
                    "action" =>
                    [
                        "verb" => "LIST",
                        "parameters" =>
                        [
                            "pageNumber" => $pageNumber,
                            "listType" => "simple",
                            "filters" => [
                                "srvExport" => "1"
                            ],
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

                foreach ($data as $projet) {
                    array_push($projets, $projet);
                }
            } catch (\Throwable $th) {
                throw $th;
            }
            $pageNumber++;
        } while ($pageNumber - 1 < $maxPageNumber);

        $this->projetLogger->info(count($projets) . " Projets Récupéré");

        return $this->checkDatabaseProjets($projets);
    }

    private function checkDatabaseProjets($crmProjets): JsonResponse
    {
        $projetRepository = $this->em->getRepository(Projet::class);
        $nbNewProjets = 0;
        $nbUpdatedProjets = 0;
        $projetFolderToCreate = [];
        $projetFolderToUpdate = [];

        foreach ($crmProjets as $crmProjet) {

            if ($crmProjet["dealgroupheader"]["final_customer_ID"]) {
                $tier = $this->divaltoTierService->getTiersByCodeCustomer($crmProjet["dealgroupheader"]["final_customer_ID"]);

                $forbiddenChars = array('\\', '/' , ':', '*', '?', '"', '<', '>', '|');

                $nomSite = $tier['name'];
                $villeSite = $tier['city'];
                $departementSite = substr($tier['postalCode'], 0, 2);
                $nomProjet = str_replace($forbiddenChars, '', $crmProjet["dealgroupheader"]["label"]);
                $anneeCreationProjet = substr($crmProjet["dealgroupheader"]["final_date_creation"], 0, 4);
                $idProjet = $crmProjet["dealgroupheader"]["codedealgroupheader"];
                $folderName = "$nomSite - $villeSite - $departementSite - $nomProjet - $anneeCreationProjet - $idProjet";


                $newProjet = new Projet();
                $newProjet->setNomSite($nomSite)
                    ->setVilleSite($villeSite)
                    ->setDepartementSite($departementSite)
                    ->setNomProjet($nomProjet)
                    ->setAnneeCreationProjet('date')
                    ->setIdProjet($idProjet)
                    ->setFolderName($folderName);

                $projet = $projetRepository->findOneBy([
                    'idProjet' => $idProjet,
                ]);

                if (!$projet) {
                    $this->em->persist($newProjet);
                    $nbNewProjets++;
                    array_push($projetFolderToCreate, $newProjet);
                } else {
                    $hasUpdate = false;
                    $oldFolderName = $projet->getFolderName();

                    if ($projet->getFolderName() != $newProjet->getFolderName()) {
                        $projet->setFolderName($newProjet->getFolderName());
                        $hasUpdate = true;
                    }

                    if ($projet->getNomSite() != $newProjet->getNomSite()) {
                        $projet->setNomSite($newProjet->getNomSite());
                        $hasUpdate = true;
                    }

                    if ($projet->getVilleSite() != $newProjet->getVilleSite()) {
                        $projet->setVilleSite($newProjet->getVilleSite());
                        $hasUpdate = true;
                    }

                    if ($projet->getDepartementSite() != $newProjet->getDepartementSite()) {
                        $projet->setDepartementSite($newProjet->getDepartementSite());
                        $hasUpdate = true;
                    }

                    if ($projet->getNomProjet() != $newProjet->getNomProjet()) {
                        $projet->setNomProjet($newProjet->getNomProjet());
                        $hasUpdate = true;
                    }

                    if ($projet->getAnneeCreationProjet() != $newProjet->getAnneeCreationProjet()) {
                        $projet->setAnneeCreationProjet($newProjet->getAnneeCreationProjet());
                        $hasUpdate = true;
                    }

                    if ($hasUpdate) {
                        $projet->setOldFolderName($oldFolderName);
                        array_push($projetFolderToUpdate, $projet);
                        $nbUpdatedProjets++;
                    }
                }
            }
        }

        $this->em->flush();

        $serverCommercial = $this->em->getRepository(Server::class)->findOneBy(['name' => 'commercial']);
        $this->divaltoProjetFolderManagerService->createOrUpdateFolderOnServer($projetFolderToCreate, $projetFolderToUpdate, $serverCommercial);

        $this->projetLogger->info($nbNewProjets . " projet(s) ajouté, " . $nbUpdatedProjets . " projet(s) mis a jour");
        return new JsonResponse($nbNewProjets . " projet(s) ajouté, " . $nbUpdatedProjets . " projet(s) mis a jour");
    }
}
