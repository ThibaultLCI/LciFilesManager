<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DivaltoConsultationService
{
    public function __construct(private EntityManagerInterface $em, private ParameterBagInterface $params, private SshService $sshService, private LoggerInterface $consultationLogger, private DivaltoTierService $divaltoTiersService, private ConsultationFolderManagerService $consultationFolderManagerService)
    {
    }

    public function fetchConsultations()
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

        $this->markConsultationForExport($consultations);

        return $this->checkDatabaseConsultations($consultations);
    }

    private function checkDatabaseConsultations($crmConsultations)
    {
        $consultationRepository = $this->em->getRepository(Consultation::class);
        $nbNewConsultations = 0;
        $nbUpdatedConsultations = 0;
        $consultationFolderToCreate = [];
        $consultationFolderToUpdate = [];

        foreach ($crmConsultations as $crmConsultation) {

            if ($crmConsultation["opportunity"]["customer_ID"]) {
                $tier = $this->divaltoTiersService->getTiersByCodeCustomer($crmConsultation["opportunity"]["customer_ID"]);

                $forbiddenChars = array('\\', '/', ':', '*', '?', '"', '<', '>', '|');

                $nomEntreprise = $tier['name'];
                $villeEntreprise = $tier['city'];
                $departementEntreprise = substr($tier['postalCode'], 0, 2);
                $nomConsultation = str_replace($forbiddenChars, '', $crmConsultation["opportunity"]["label"]);
                $anneeCreationConsultation = substr($crmConsultation["opportunity"]["creationDate"], 0, 4);
                $idConsultation = $crmConsultation["opportunity"]["codeopportunity"];
                $folderName = "$nomEntreprise - $villeEntreprise - $departementEntreprise - $nomConsultation - $anneeCreationConsultation - $idConsultation";

                $newConsultation = new Consultation();
                $newConsultation->setNomEntreprise($nomEntreprise)
                    ->setVilleEntreprise($villeEntreprise)
                    ->setDepartementEntreprise($departementEntreprise)
                    ->setNomConsultation($nomConsultation)
                    ->setAnneeCreationConsultation($anneeCreationConsultation)
                    ->setIdCrm($idConsultation)
                    ->setFolderName($folderName);

                $consultation = $consultationRepository->findOneBy([
                    'idCrm' => $crmConsultation["opportunity"]["codeopportunity"],
                ]);

                if (!$consultation) {
                    $this->em->persist($newConsultation);
                    $nbNewConsultations++;
                    array_push($consultationFolderToCreate, $newConsultation);
                } else {
                    $hasUpdate = false;
                    $oldFolderName = $consultation->getFolderName();

                    if ($consultation->getFolderName() != $newConsultation->getFolderName()) {
                        $consultation->setFolderName($newConsultation->getFolderName());
                        $hasUpdate = true;
                    }

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
                        $consultation->setOldFolderName($oldFolderName);
                        array_push($consultationFolderToUpdate, $consultation);
                        $nbUpdatedConsultations++;
                    }
                }
            }
        }

        $this->em->flush();

        $serverCommercial = $this->em->getRepository(Server::class)->findOneBy(['isServerCommercial' => true]);
        $commands = $this->consultationFolderManagerService->createOrUpdateFolderOnServer($consultationFolderToCreate, $consultationFolderToUpdate, $serverCommercial, "\\------ CONSULTATION CRM\\");

        $this->consultationLogger->info($nbNewConsultations . " consultation(s) ajouté, " . $nbUpdatedConsultations . " consultation(s) mis a jour");
        return $commands;
    }

    private function markConsultationForExport(array $consultations)
    {
        $url = $this->params->get('divalto_consultation_url');

        foreach ($consultations as $consultation) {
            try {
                $params = [
                    "header" =>
                    [
                        "languageCode" => "FR",
                        "markForExport" => 0
                    ],
                    "action" =>
                    [
                        "verb" => "PUT",
                    ],
                    "data" => [
                        "opportunity" => [
                            "codeopportunity" => $consultation["opportunity"]["codeopportunity"],
                            "customer_ID" => $consultation["opportunity"]["customer_ID"],
                            "label" =>  $consultation["opportunity"]["label"],
                            "generictype_ID_opportunityType" =>  $consultation["opportunity"]["generictype_ID_opportunityType"],
                        ]
                    ]
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
            } catch (\Throwable $th) {
                throw $th;
            }
        }
    }
}
