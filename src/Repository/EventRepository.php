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
class EventRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
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
                ->andWhere('c.id = :campus')
                ->setParameter('campus', $campus);
        }
        if ($name) {
            $query->andWhere('e.name LIKE :name')
                ->setParameter('name', '%' . addcslashes($name, '%_') . '%');;
        }
        if ($from) {
            $query->andWhere('e.startDate >= :from')
                ->setParameter('from', $from);
        }
        if ($to) {
            $query->andWhere('e.startDate <= :to')
                ->setParameter('to', $to->setTime(23, 59, 59));
        }
        if ($organized) {
            $query->andWhere('e.organizer = :organizer')
                ->setParameter('organizer', $user);
        }
        if ($subscribed) {
            $query->andWhere(':participant MEMBER OF e.participants')
                ->setParameter('participant', $user);
        }
        if ($notSubscribed) {
            $query->andWhere(':notParticipant NOT MEMBER OF e.participants')
                ->setParameter('notParticipant', $user);
        }
        if ($over) {
            $query->andWhere('e.startDate < CURRENT_TIMESTAMP()');
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
