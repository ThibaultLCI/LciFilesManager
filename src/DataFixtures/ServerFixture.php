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
            // [
            //     "nom" => "Photos",
            //     "ip" => "10.1.10.14",
            //     "port" => 22,
            //     "isServerCommercial" => false,
            //     "folders" => [
            //         "F:/Photos"
            //     ]
            // ],
            // [
            //     "nom" => "SAV France",
            //     "ip" => "10.2.10.11",
            //     "port" => 22,
            //     "isServerCommercial" => false,
            //     "folders" => [
            //         "D:/Data/Partages"
            //     ]
            // ],
            // [
            //     "nom" => "Commercial",
            //     "ip" => "10.1.10.11",
            //     "port" => 22,
            //     "isServerCommercial" => true,
            //     "folders" => [
            //         "F:\Datas\Partages\------ COMMERCIAL"
            //     ]
            // ],
            // [
            //     "nom" => "Photos",
            //     "ip" => "10.1.10.36",
            //     "port" => 22,
            //     "isServerCommercial" => false,
            //     "folders" => [
            //         "E:\CRM\---- CLIENTS CRM"
            //     ]
            // ],
            // [
            //     "nom" => "SAV France",
            //     "ip" => "10.1.10.36",
            //     "port" => 22,
            //     "isServerCommercial" => false,
            //     "folders" => [
            //         "E:\CRM\------ SAV FRANCE CRM"
            //     ]
            // ],
            [
                "nom" => "Commercial",
                "ip" => "10.1.10.36",
                "port" => 22,
                "isServerCommercial" => true,
                "folders" => [
                    "\\\SRV-CAR-CRM\CRM\------ COMMERCIAL"
                ]
            ],
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
