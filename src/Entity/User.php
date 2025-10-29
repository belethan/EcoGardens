<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/** @noinspection PhpUnused */
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "La ville est obligatoire.")]
    private ?string $ville = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $code_postal = null;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, conseil>
     */
    #[ORM\OneToMany(targetEntity: Conseil::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $conseils;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles ;

    public function __construct()
    {
        $this->conseils = new ArrayCollection();
        $this->created_at = new DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
    }
    // 🧩 Méthode obligatoire : identifiant unique
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
    // Pour compatibilité ascendante (facultatif, mais conseillé)
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    // 🧩 Rôles utilisateur
    public function getRoles(): array
    {
        $roles = $this->roles;
        // garantir au moins ROLE_USER
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    // 🧩 Mot de passe haché
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    // 🧩 Si tu stockes des infos temporaires sensibles (optionnel)
    public function eraseCredentials(): void
    {
        // Si tu stockes le mot de passe en clair temporairement :
        // $this->plainPassword = null;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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

    public function getCodePostal(): ?string
    {
        return $this->code_postal;
    }

    public function setCodePostal(?string $code_postal): static
    {
        $this->code_postal = $code_postal;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, conseil>
     */
    public function getConseil(): Collection
    {
        return $this->conseils;
    }

    public function addConseil(conseil $conseil): static
    {
        if (!$this->conseils->contains($conseil)) {
            $this->conseils->add($conseil);
            $conseil->setUser($this);
        }

        return $this;
    }

    public function removeConseil(conseil $conseil): static
    {
        if ($this->conseils->removeElement($conseil) && $conseil->getUser() === $this) {
            $conseil->setUser(null);
        }

        return $this;
    }

}
