<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    private ?string $departementSite = null;

    #[ORM\Column(length: 255)]
    private ?string $folderName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oldFolderName = null;

    #[ORM\OneToMany(mappedBy: 'projet', targetEntity: Consultation::class)]
    private Collection $consultations;

    #[ORM\Column(length: 255)]
    private ?string $idCrm = null;

    public function __construct()
    {
        $this->consultations = new ArrayCollection();
    }

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

    public function getDepartementSite(): ?string
    {
        return $this->departementSite;
    }

    public function setDepartementSite(string $departementSite): static
    {
        $this->departementSite = $departementSite;

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

    public function setOldFolderName(?string $oldFolderName): static
    {
        $this->oldFolderName = $oldFolderName;

        return $this;
    }

    /**
     * @return Collection<int, Consultation>
     */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): static
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations->add($consultation);
            $consultation->setProjet($this);
        }

        return $this;
    }

    public function removeConsultation(Consultation $consultation): static
    {
        if ($this->consultations->removeElement($consultation)) {
            // set the owning side to null (unless already changed)
            if ($consultation->getProjet() === $this) {
                $consultation->setProjet(null);
            }
        }

        return $this;
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
}
