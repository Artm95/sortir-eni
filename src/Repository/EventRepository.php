<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function getAllWithOrganizer() {
        return $this->createQueryBuilder('e')
            ->addSelect('o')
            ->join('e.organizer', 'o')
            ->addOrderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search($campus, $name, $from, $to, $organized, $subscribed, $notSubscribed, $over) {
        $query = $this->createQueryBuilder('e')
            ->addSelect('o')
            ->join('e.organizer', 'o');
        if ($campus) {
            $query->addSelect('c')
                ->join('e.campus', 'c')
                ->where('c.id = :campusId')
                ->setParameter('campusId', $campus);
        }
        if ($name) {
            $query->where('e.name LIKE :name')
                ->setParameter('val', '%' . addcslashes($name, '%_') . '%');
        }
        if ($from) {
            $query->where('e.startDate >= :from')
                ->setParameter('from', $from);
        }
        if ($to) {
            $query->where('e.startDate <= :to')
                ->setParameter('to', $to);
        }
        if ($organized) {
            $query->where('e.organizer = :organizer')
                ->setParameter(':organizer', $from); //TODO: retrieve user from session for the parameter value
        }
        if ($from) {
            $query->where('e.startDate >= :from')
                ->setParameter('from', $from);
        }
        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return Event[] Returns an array of Event objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Event
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
