<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Projet;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DivaltoProjetHasConsultationService
{
    public function __construct(private ParameterBagInterface $params, private LoggerInterface $projetHasConsultationLogger, private EntityManagerInterface $em, private DivaltoProjetHasConsultationFolderManagerService $divaltoProjetHasConsultationFolderManagerService)
    {
    }

    public function fetchRelations()
    {
        $url = $this->params->get('divalto_projetdetail_url');

        $pageNumber = 1;
        $maxPageNumber = 0;
        $relations = [];

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

                foreach ($data as $relation) {
                    array_push($relations, $relation);
                }
            } catch (\Throwable $th) {
                throw $th;
            }
            $pageNumber++;
        } while ($pageNumber - 1 < $maxPageNumber);

        $this->projetHasConsultationLogger->info(count($relations) . " Relation Récupéré");

        return $this->makeProjectConsultationRelation($relations);
    }

    private function makeProjectConsultationRelation($crmProjectConsultationRelations)
    {

        $relations = [];

        foreach ($crmProjectConsultationRelations as $crmRelation) {
            $projet = $this->em->getRepository(Projet::class)->findOneBy(['idProjet' => $crmRelation["dealgroupdetail"]['dealgroupheader_ID']]);


            if ($projet) {
                $idProjet = $projet->getIdProjet();
                $dealID = $crmRelation["dealgroupdetail"]['deal_ID'];

                if (!array_key_exists($idProjet, $relations)) {
                    $relations[$idProjet] = [];
                }

                if (!in_array($dealID, $relations[$idProjet])) {
                    $relations[$idProjet][] = $dealID;
                }
            }
        }

        foreach ($relations as $projetId => $crmConsultations) {
            $projet = $this->em->getRepository(Projet::class)->findOneBy(["idProjet" => $projetId]);

            $this->removeNotFindConsultation($projet, $crmConsultations);

            foreach ($crmConsultations as $crmConsultation) {
                $consultation = $this->em->getRepository(Consultation::class)->findOneBy(["idConsultation" => $crmConsultation]);

                if ($consultation) {
                    $projet->addConsultation($consultation);
                }
            }
        }

        $this->em->flush();
        $this->projetHasConsultationLogger->info("Relation Projet <=> Consultation créée");
        $this->divaltoProjetHasConsultationFolderManagerService->manageShortCut();
    }

    public function removeNotFindConsultation(Projet $projet, array $consultations)
    {
        // Obtenir les consultations actuelles du projet
        $currentConsultations = $projet->getConsultations();

        // Itérer sur les consultations actuelles du projet
        foreach ($currentConsultations as $consultation) {
            // Si l'ID de la consultation actuelle n'est pas dans le tableau de nouvelles consultations
            if (!in_array($consultation->getIdConsultation(), $consultations)) {
                // Retirer la consultation de l'entité Projet
                $projet->removeConsultation($consultation);
                // Supprimer la consultation de l'EntityManager
                $this->em->remove($consultation);
            }
        }
    }
}
