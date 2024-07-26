<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
class Projet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomSite = null;

    #[ORM\Column(length: 255)]
    private ?string $villeSite = null;

    #[ORM\Column(length: 255)]
    private ?string $nomProjet = null;

    #[ORM\Column(length: 255)]
    private ?string $anneeCreationProjet = null;

    #[ORM\Column(length: 255)]
    private ?string $idProjet = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSite(): ?string
    {
        return $this->nomSite;
    }

    public function setNomSite(string $nomSite): static
    {
        $this->nomSite = $nomSite;

        return $this;
    }

    public function getVilleSite(): ?string
    {
        return $this->villeSite;
    }

    public function setVilleSite(string $villeSite): static
    {
        $this->villeSite = $villeSite;

        return $this;
    }

    public function getNomProjet(): ?string
    {
        return $this->nomProjet;
    }

    public function setNomProjet(string $nomProjet): static
    {
        $this->nomProjet = $nomProjet;

        return $this;
    }

    public function getAnneeCreationProjet(): ?string
    {
        return $this->anneeCreationProjet;
    }

    public function setAnneeCreationProjet(string $anneeCreationProjet): static
    {
        $this->anneeCreationProjet = $anneeCreationProjet;

        return $this;
    }

    public function getIdProjet(): ?string
    {
        return $this->idProjet;
    }

    public function setIdProjet(string $idProjet): static
    {
        $this->idProjet = $idProjet;

        return $this;
    }
}
