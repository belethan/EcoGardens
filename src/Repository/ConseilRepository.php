<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<conseil>
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
    public function findByMoisAnnee(int $mois, ?int $annee = null): array
    {
        $annee = $annee ?? (int) (new \DateTimeImmutable())->format('Y');

        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.tempsConseils', 't')
            ->addSelect('t')
            ->leftJoin('c.user', 'u')   // si tu veux renvoyer l’email auteur
            ->addSelect('u')
            ->andWhere('t.mois = :mois')
            ->andWhere('t.annee = :annee')
            ->setParameter('mois', $mois)
            ->setParameter('annee', $annee)
            ->orderBy('c.createdAt', 'DESC')
            ->distinct(); // évite les doublons si plusieurs TempsConseil matchent par mégarde

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les conseils du mois en cours.
     */
    public function findForCurrentMonth(): array
    {
        $now = new \DateTime();
        return $this->findByMoisAnnee((int) $now->format('m'), (int) $now->format('Y'));
    }

    public function existsForConseil(Conseil $conseil, int $mois, int $annee): bool
    {
        return (bool) $this->createQueryBuilder('t')
            ->select('1')
            ->andWhere('t.conseil = :c')
            ->andWhere('t.mois = :m')
            ->andWhere('t.annee = :a')
            ->setParameter('c', $conseil)
            ->setParameter('m', $mois)
            ->setParameter('a', $annee)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
