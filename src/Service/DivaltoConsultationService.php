<?php

namespace App\Service;

use App\Entity\Consultation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DivaltoConsultationService
{
    public function __construct(private EntityManagerInterface $em, private ParameterBagInterface $params, private FolderManagerService $folderManagerService, private SshService $sshService, private LoggerInterface $consultationLogger, private DivaltoTierService $divaltoTiersService)
    {
    }

    public function fetchConsultations(): JsonResponse
    {
        $url = $this->params->get('divalto_consultation_url');

        $pageNumber = 1;
        $maxPageNumber = 0;
        $consultations = [];

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

                foreach ($data as $consultation) {
                    array_push($consultations, $consultation);
                }
            } catch (\Throwable $th) {
                throw $th;
            }
            $pageNumber++;
        } while ($pageNumber - 1 < $maxPageNumber);

        $this->consultationLogger->info(count($consultations) . " Consultations Récupéré");

        return $this->checkDatabaseConsultations($consultations);
    }

    private function checkDatabaseConsultations($crmConsultations): JsonResponse
    {

        $consultationRepository = $this->em->getRepository(Consultation::class);

        foreach ($crmConsultations as $crmConsultation) {
            $nbNewConsultations = 0;
            $nbUpdatedConsultations = 0;

            if ($crmConsultation["opportunity"]["customer_ID"]) {
                $tier = $this->divaltoTiersService->getTiersByCodeCustomer($crmConsultation["opportunity"]["customer_ID"]);

                $newConsultation = new Consultation();
                $newConsultation->setNomEntreprise($tier['name'])
                    ->setVilleEntreprise($tier['city'])
                    ->setDepartementEntreprise(substr($tier['postalCode'], 0, 2))
                    ->setNomConsultation($crmConsultation["opportunity"]["label"])
                    ->setAnneeCreationConsultation($crmConsultation["opportunity"]["creationDate"])
                    ->setIdConsultation($crmConsultation["opportunity"]["codeopportunity"]);

                $consultation = $consultationRepository->findOneBy([
                    'idConsultation' => $crmConsultation["opportunity"]["codeopportunity"],
                ]);

                if (!$consultation) {
                    $this->em->persist($newConsultation);
                    $nbNewConsultations++;
                } else {
                    $hasUpdate = false;
    
                    if ($consultation->getNomEntreprise() != $newConsultation->getNomEntreprise()) {
                        $consultation->setNomEntreprise($newConsultation->getNomEntreprise());
                        $hasUpdate = true;
                    }
    
                    if ($consultation->getVilleEntreprise() != $newConsultation->getVilleEntreprise()) {
                        $consultation->setVilleEntreprise($newConsultation->getVilleEntreprise());
                        $hasUpdate = true;
                    }

                    if ($consultation->getDepartementEntreprise() != $newConsultation->getDepartementEntreprise()) {
                        $consultation->setDepartementEntreprise($newConsultation->getDepartementEntreprise());
                        $hasUpdate = true;
                    }

                    if ($consultation->getNomConsultation() != $newConsultation->getNomConsultation()) {
                        $consultation->setNomConsultation($newConsultation->getNomConsultation());
                        $hasUpdate = true;
                    }

                    if ($consultation->getAnneeCreationConsultation() != $newConsultation->getAnneeCreationConsultation()) {
                        $consultation->setAnneeCreationConsultation($newConsultation->getAnneeCreationConsultation());
                        $hasUpdate = true;
                    }
    
                    if ($hasUpdate) {
                        $nbUpdatedConsultations++;
                    }
                }
            }
        }


        $this->em->flush();
        $this->consultationLogger->info($nbNewConsultations . " consultation(s) ajouté, " . $nbUpdatedConsultations . " consultation(s) mis a jour");
        return new JsonResponse($nbNewConsultations . " consultation(s) ajouté, " . $nbUpdatedConsultations . " consultation(s) mis a jour");
    }
}
