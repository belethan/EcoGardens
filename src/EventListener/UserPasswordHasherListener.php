<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHasherListener
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Lors de la création d'un utilisateur (INSERT)
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $plainPassword = $entity->getPassword();

        // Si un mot de passe est défini et qu’il n’est pas encore hashé
        if ($plainPassword && !str_starts_with($plainPassword, '$2y$')) {
            $hashed = $this->passwordHasher->hashPassword($entity, $plainPassword);
            $entity->setPassword($hashed);
        }
    }

    /**
     * Lors de la mise à jour d'un utilisateur (UPDATE)
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $mustRehash = false;

        // ✅ Si le mot de passe a été modifié
        if ($args->hasChangedField('password')) {
            $mustRehash = true;
        }

        // ✅ Si l'email a été modifié, on rehash aussi le mot de passe
        if ($args->hasChangedField('email')) {
            $mustRehash = true;
        }

        if ($mustRehash) {
            $plainPassword = $entity->getPassword();

            // Ne rehash que si ce n’est pas déjà un hash
            if ($plainPassword && !str_starts_with($plainPassword, '$2y$')) {
                $hashed = $this->passwordHasher->hashPassword($entity, $plainPassword);
                $entity->setPassword($hashed);

                // ⚠️ Doctrine doit être informé que le champ a changé
                $em = $args->getObjectManager();
                $classMetadata = $em->getClassMetadata(User::class);
                $em->getUnitOfWork()->recomputeSingleEntityChangeSet($classMetadata, $entity);
            }
        }
    }
}
