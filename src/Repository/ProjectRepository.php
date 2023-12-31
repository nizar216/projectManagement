<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }
    public function findByFilter(array $filterData = [])
    {
        $qb = $this->createQueryBuilder('p');

        if (isset($filterData['title']) && !empty($filterData['title'])) {
            $qb->andWhere('p.title LIKE :title')
                ->setParameter('title', '%' . $filterData['title'] . '%');
        }

        if (isset($filterData['status']) && !empty($filterData['status'])) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $filterData['status']);
        }

        if (isset($filterData['filename']) && !empty($filterData['filename'])) {
            $qb->andWhere('p.filename LIKE :filename')
                ->setParameter('filename', '%' . $filterData['filename'] . '%');
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Project[] Returns an array of Project objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Project
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
