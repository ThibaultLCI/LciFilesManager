<?php

namespace App\DataFixtures;

use App\Entity\Folder;
use App\Entity\Server;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServerFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $servers = [
            [
                "nom" => "Photos",
                "ip" => "10.1.10.14",
                "port" => 22,
                "isServerCommercial" => false,
                "folders" => [
                    "\\\SRV-CAR-ARCHIVE\Photos\---- CLIENTS CRM"
                ]
            ],
            [
                "nom" => "Wattviller",
                "ip" => "10.2.10.11",
                "port" => 22,
                "isServerCommercial" => false,
                "folders" => [
                    "\\\SRV-WATT-DATA\Partages\------ SAV FRANCE CRM"
                ]
            ],
            [
                "nom" => "Carvin",
                "ip" => "10.1.10.11",
                "port" => 22,
                "isServerCommercial" => true,
                "folders" => [
                    "\\\SRV-CAR-DATA\Partages\------ COMMERCIAL"
                ]
            ],
            //For Test
            // [
            //     "nom" => "Photos",
            //     "ip" => "10.1.10.36",
            //     "port" => 22,
            //     "isServerCommercial" => false,
            //     "folders" => [
            //         "\\\SRV-CAR-CRM\CRM\------ CLIENTS CRM"
            //     ]
            // ],
            // [
            //     "nom" => "SAV France",
            //     "ip" => "10.1.10.36",
            //     "port" => 22,
            //     "isServerCommercial" => false,
            //     "folders" => [
            //         "\\\SRV-CAR-CRM\CRM\------ SAV FRANCE CRM"
            //     ]
            // ],
            // [
            //     "nom" => "Commercial",
            //     "ip" => "10.1.10.36",
            //     "port" => 22,
            //     "isServerCommercial" => true,
            //     "folders" => [
            //         "\\\SRV-CAR-CRM\CRM\------ COMMERCIAL"
            //     ]
            // ],
        ];

        foreach ($servers as $server) {
            $newServer = new Server();

            $newServer->setName($server["nom"])
                ->setHost($server["ip"])
                ->setPort($server["port"])
                ->setIsServerCommercial($server["isServerCommercial"]);

            foreach ($server["folders"] as $folder) {
                $newFolder = new Folder();

                $newFolder->setPath($folder);

                $newServer->addFolder($newFolder);
            }

            $manager->persist($newServer);
        }

        $manager->flush();
    }
}
