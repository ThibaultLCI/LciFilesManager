<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DivaltoTierService
{
    public function __construct(private EntityManagerInterface $em, private ParameterBagInterface $params, private FolderManagerService $folderManagerService, private SshService $sshService, private LoggerInterface $logger)
    {
    }

    public function getTiersByCodeCustomer(String $codeCustomer)
    {

        $url = $this->params->get('divalto_customer_url');

        try {
            $params = [
                "header" =>
                [
                    "languageCode" => "FR",
                ],
                "action" =>
                [
                    "verb" => "GET", // GET, PUT, DELETE, LIST, DEFINITION
                    "parameters" =>
                    [
                        "code" => $codeCustomer,
                        "listType" => "simple",
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

            $tier = $result["result"]["response"]["data"][0]['customer'];

            return $tier;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
