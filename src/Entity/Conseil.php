<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(name: 'created_At', type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_At', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'conseils')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, TempsConseil>
     */
    #[ORM\OneToMany(targetEntity: TempsConseil::class, mappedBy: 'conseil', orphanRemoval: true)]
    private Collection $tempsConseils;

    public function __construct()
    {
        $this->tempsConseils = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

       public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, TempsConseil>
     */
    public function getTempsConseils(): Collection
    {
        return $this->tempsConseils;
    }

    public function addTempsConseil(tempsConseil $tempsConseil): static
    {
        if (!$this->tempsConseils->contains($tempsConseil)) {
            $this->tempsConseils->add($tempsConseil);
            $tempsConseil->setConseil($this);
        }

        return $this;
    }

    public function removeTempsConseil(tempsConseil $tempsConseil): static
    {
        if ($this->tempsConseils->removeElement($tempsConseil) && $tempsConseil->getConseil() === $this) {
            $tempsConseil->setConseil(null);
        }
        return $this;
    }

    /**
     * Retourne une représentation JSON-friendly du Conseil.
     */
    public function toApiArray(): array
    {
        return [
            'id'          => $this->getId(),
            'titre'       => method_exists($this, 'getTitre') ? $this->getTitre() : null, // facultatif si tu as un titre
            'contenu'     => method_exists($this, 'getContenu') ? $this->getContenu() : null,
            'auteurEmail' => $this->getUser()?->getEmail(),
            'temps'       => array_map(
               static fn($t) => [
                    'id'     => $t->getId(),
                    'mois'   => $t->getMois(),
                    'annee'  => $t->getAnnee(),
                ],
                $this->getTempsConseils()?->toArray() ?? []
            ),
            'created_at'  => $this->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'  => $this->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

}
