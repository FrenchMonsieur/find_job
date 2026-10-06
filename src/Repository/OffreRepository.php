<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offre>
 */
class OffreRepository extends ServiceEntityRepository
{
    public function compterARelancer(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.statut = :statut')
            ->andWhere('COALESCE(o.dateRelance, o.dateCandidature) < :limite')
            ->setParameter('statut', 'envoyee')
            ->setParameter('limite', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();
    }
    /** Renvoie par exemple ['nouvelle' => 12, 'a_postuler' => 3] */
    public function compterParStatut(): array
    {
        $lignes = $this->createQueryBuilder('o')
            ->select('o.statut, COUNT(o.id) AS total')
            ->groupBy('o.statut')
            ->getQuery()
            ->getArrayResult();

        return array_column($lignes, 'total', 'statut');
    }
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    //    /**
//     * @return Offre[] Returns an array of Offre objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    //    public function findOneBySomeField($value): ?Offre
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
