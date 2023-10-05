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

    #[ORM\Column(length: 255)]
    private ?string $ville = null;

    #[ORM\ManyToMany(targetEntity: Folder::class, inversedBy: 'sites')]
    private Collection $folders;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oldIntitule = null;


    public function __construct()
    {
        $this->folders = new ArrayCollection();
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


    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    /**
     * @return Collection<int, Folder>
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    public function addFolder(Folder $folder): static
    {
        if (!$this->folders->contains($folder)) {
            $this->folders->add($folder);
        }

        return $this;
    }

    public function removeFolder(Folder $folder): static
    {
        $this->folders->removeElement($folder);

        return $this;
    }

    public function getOldIntitule(): ?string
    {
        return $this->oldIntitule;
    }

    public function setOldIntitule(?string $oldIntitule): static
    {
        $this->oldIntitule = $oldIntitule;

        return $this;
    }
}
