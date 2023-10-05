<?php

namespace App\Service;

use App\Entity\Server;
use phpseclib3\Net\SSH2;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SshService
{
    private $nbOuvertureSSh = 0 ;

    public function __construct(private ParameterBagInterface $params,)
    {
    }

    public function connexion(Server $server) : SSH2 {
        $userSSH = $this->params->get('user_ssh');

        $ssh = new SSH2($server->getHost(), $server->getPort());

        if (!$ssh->login($userSSH['username'], $userSSH['password'])) {
            die('Échec de l\'authentification SSH sur le serveur' . $server['name']);
        }

        $this->nbOuvertureSSh++ ;

        return $ssh ;
    }

    public function deconnexion(SSH2 $ssh) : void {
        $ssh->disconnect();
    }

    public function getNbOuvertureSsh() : int{
        return $this->nbOuvertureSSh;
    }
}
