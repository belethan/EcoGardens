<?php

namespace App\Entity;

use App\Repository\tempsConseilRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: tempsConseilRepository::class)]
class TempsConseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $mois = null;

    #[ORM\Column]
    private ?int $annee = null;

    #[ORM\ManyToOne(targetEntity: Conseil::class, inversedBy: 'tempsConseils')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conseil $conseil = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMois(): ?int
    {
        return $this->mois;
    }

    public function setMois(int $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function getConseil(): ?conseil
    {
        return $this->conseil;
    }

    public function setConseil(?conseil $Conseil): static
    {
        $this->conseil = $Conseil;

        return $this;
    }
}
