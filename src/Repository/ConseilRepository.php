<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conseil>
 */
class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    /**
     * Récupère les conseils pour un mois et une année donnés.
     */
    public function findByMoisAnnee(int $mois, int $annee): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.mois = :mois')
            ->andWhere('c.annee = :annee')
            ->setParameters(['mois' => $mois, 'annee' => $annee])
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les conseils du mois en cours.
     */
    public function findForCurrentMonth(): array
    {
        $now = new \DateTime();
        return $this->findByMoisAnnee((int) $now->format('m'), (int) $now->format('Y'));
    }
}
