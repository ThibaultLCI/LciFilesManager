<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idCrm = null;

    #[ORM\Column(length: 255)]
    private ?string $intitule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $informationsClient = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 255)]
    private ?string $ville = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienGoolgle = null;

    #[ORM\ManyToMany(targetEntity: Server::class, inversedBy: 'sites')]
    private Collection $serveurs;

    public function __construct()
    {
        $this->serveurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdCrm(): ?string
    {
        return $this->idCrm;
    }

    public function setIdCrm(string $idCrm): static
    {
        $this->idCrm = $idCrm;

        return $this;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getinformationsClient(): ?string
    {
        return $this->informationsClient;
    }

    public function setinformationsClient(?string $informationsClient): static
    {
        $this->informationsClient = $informationsClient;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): static
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getLienGoolgle(): ?string
    {
        return $this->lienGoolgle;
    }

    public function setLienGoolgle(?string $lienGoolgle): static
    {
        $this->lienGoolgle = $lienGoolgle;

        return $this;
    }

    /**
     * @return Collection<int, Server>
     */
    public function getServeurs(): Collection
    {
        return $this->serveurs;
    }

    public function addServeur(Server $serveur): static
    {
        if (!$this->serveurs->contains($serveur)) {
            $this->serveurs->add($serveur);
        }

        return $this;
    }

    public function removeServeur(Server $serveur): static
    {
        $this->serveurs->removeElement($serveur);

        return $this;
    }
}
