<?php

namespace App\Service;

use App\Entity\Server;
use phpseclib3\Net\SSH2;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SshService
{
    private $nbOuvertureSSh = 0;

    public function __construct(private ParameterBagInterface $params, private LoggerInterface $logger)
    {
    }

    public function connexion(Server $server): SSH2
    {
        $start = microtime(true);

        $userSSH = $this->params->get('user_ssh');

        $ssh = new SSH2($server->getHost(), $server->getPort());

        if (!$ssh->login($userSSH['username'], $userSSH['password'])) {
            die('Échec de l\'authentification SSH sur le serveur' . $server['name']);
        }

        $this->nbOuvertureSSh++;

        $end = microtime(true) - $start;
        $this->logger->info("temps connexion ssh pour le server : " . $server->getName() . " : " . $end . "\n");


        return $ssh;
    }

    public function deconnexion(SSH2 $ssh): void
    {
        $ssh->disconnect();
    }

    public function getNbOuvertureSsh(): string
    {
        return "nombre de connexion ssh effectué : " . $this->nbOuvertureSSh . "\n";
    }
}
