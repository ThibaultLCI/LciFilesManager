<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomEntreprise = null;

    #[ORM\Column(length: 255)]
    private ?string $villeEntreprise = null;

    #[ORM\Column(length: 255)]
    private ?string $departementEntreprise = null;

    #[ORM\Column(length: 255)]
    private ?string $nomConsultation = null;

    #[ORM\Column(length: 255)]
    private ?string $anneeCreationConsultation = null;

    #[ORM\Column(length: 255)]
    private ?string $idConsultation = null;

    #[ORM\Column(length: 255)]
    private ?string $folderName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oldFolderName = null;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    private ?Projet $projet = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEntreprise(): ?string
    {
        return $this->nomEntreprise;
    }

    public function setNomEntreprise(string $nomEntreprise): static
    {
        $this->nomEntreprise = $nomEntreprise;

        return $this;
    }

    public function getVilleEntreprise(): ?string
    {
        return $this->villeEntreprise;
    }

    public function setVilleEntreprise(string $villeEntreprise): static
    {
        $this->villeEntreprise = $villeEntreprise;

        return $this;
    }

    public function getDepartementEntreprise(): ?string
    {
        return $this->departementEntreprise;
    }

    public function setDepartementEntreprise(string $departementEntreprise): static
    {
        $this->departementEntreprise = $departementEntreprise;

        return $this;
    }

    public function getNomConsultation(): ?string
    {
        return $this->nomConsultation;
    }

    public function setNomConsultation(string $nomConsultation): static
    {
        $this->nomConsultation = $nomConsultation;

        return $this;
    }

    public function getAnneeCreationConsultation(): ?string
    {
        return $this->anneeCreationConsultation;
    }

    public function setAnneeCreationConsultation(string $anneeCreationConsultation): static
    {
        $this->anneeCreationConsultation = $anneeCreationConsultation;

        return $this;
    }

    public function getIdConsultation(): ?string
    {
        return $this->idConsultation;
    }

    public function setIdConsultation(string $idConsultation): static
    {
        $this->idConsultation = $idConsultation;

        return $this;
    }

    public function getFolderName(): ?string
    {
        return $this->folderName;
    }

    public function setFolderName(string $folderName): static
    {
        $this->folderName = $folderName;

        return $this;
    }

    public function getOldFolderName(): ?string
    {
        return $this->oldFolderName;
    }

    public function setOldFolderName(string $oldFolderName): static
    {
        $this->oldFolderName = $oldFolderName;

        return $this;
    }

    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    public function setProjet(?Projet $projet): static
    {
        $this->projet = $projet;

        return $this;
    }
}
