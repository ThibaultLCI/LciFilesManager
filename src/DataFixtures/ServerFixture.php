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
                "folders" => [
                    "F:/Photos"
                ]
            ],
            [
                "nom" => "SAV France",
                "ip" => "10.2.10.11",
                "port" => 22,
                "folders" => [
                    "D:/Data/Partages"
                ]
            ],
            [
                "nom" => "CRM",
                "ip" => "10.1.10.36",
                "port" => 22,
                "folders" => [
                    "E:/CRM"
                ]
            ],
            [
                "nom" => "Commercial",
                "ip" => "10.1.10.11",
                "port" => 22,
                "folders" => [
                    "F:\Datas\Partages\------ COMMERCIAL"
                ]
            ],
        ];

        foreach ($servers as $server) {
            $newServer = new Server();

            $newServer->setName($server["nom"])
                ->setHost($server["ip"])
                ->setPort($server["port"]);

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
