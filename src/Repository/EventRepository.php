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
            ->addSelect('s')
            ->join('e.organizer', 'o')
            ->join('e.state', 's')
            ->getQuery()
            ->getResult();
    }

    public function search($campus, $name, $from, $to, $organized, $subscribed, $notSubscribed, $over, $user) {
        $query = $this->createQueryBuilder('e')
            ->addSelect('o')
            ->addSelect('s')
            ->join('e.organizer', 'o')
            ->join('e.state', 's');
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

    public function statesUpdate() {
        $em = $this->getEntityManager();
        $expr = $em->getExpressionBuilder();

        $monthAgo = date_sub(new \DateTime(), new \DateInterval('P1M'));
        $monthOldQuery = $this->createQueryBuilder('e1')
            ->innerJoin('e1.state', 's1')
            ->andWhere('e1.startDate <= :monthAgo')
            ->andWhere("s1.label != 'Activité historisée'")
            ->getDQL();
        $closeDeadLine = $this->createQueryBuilder('e2')
            ->innerJoin('e2.state', 's2')
            ->andWhere('e2.signUpDeadline <= CURRENT_DATE()')
            ->andWhere('s2.label = :label')
            ->getDQL();
        $currentQuery = $this->createQueryBuilder('e3')
            ->innerJoin('e3.state', 's3')
            ->andWhere('e3.startDate <= CURRENT_TIMESTAMP()')
            ->andWhere('s3.label NOT IN (:labels)')
            ->getDQL();

        return $this->createQueryBuilder('e')
            ->addSelect('s')
            ->join('e.state', 's')
            ->andWhere($expr->in('e.id', $monthOldQuery))
            ->orWhere($expr->in('e.id', $currentQuery))
            ->orWhere($expr->in('e.id', $closeDeadLine))
            ->setParameter('monthAgo', $monthAgo)
            ->setParameter('label', 'Ouverte')
            ->setParameter('labels', ['Activité historisée', 'Annulée', 'Activité terminée'])
            ->getQuery()
            ->getResult();
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
