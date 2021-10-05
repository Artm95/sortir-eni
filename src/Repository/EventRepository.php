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

    public function search($campus, $name, $from, $to, $organized, $subscribed, $notSubscribed, $over, $user) {
        $query = $this->createQueryBuilder('e')
            ->addSelect('o')
            ->join('e.organizer', 'o');
        if ($campus) {
            $query->addSelect('c')
                ->join('e.campus', 'c')
                ->andWhere('c.id = :campusId');
        }
        if ($name) $query->andWhere('e.name LIKE :name');
        if ($from) $query->andWhere('e.startDate >= :from');
        if ($to) $query->andWhere('e.startDate <= :to');
        if ($organized) $query->andWhere('e.organizer = :organizer');
        if ($subscribed) $query->andWhere(':participant MEMBER OF e.participants');
        if ($notSubscribed) $query->andWhere(':participant NOT MEMBER OF e.participants');

        if ($campus) $query->setParameter('campusId', $campus);
        if ($name) $query->setParameter('name', '%' . addcslashes($name, '%_') . '%');
        if ($from) $query->setParameter('from', $from);
        if ($to) $query->setParameter('to', $to);
        if ($organized) $query->setParameter('organizer', $user);
        if ($subscribed) $query->setParameter('participant', $user);
        if ($notSubscribed) $query->setParameter('notParticipant', $user);

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
