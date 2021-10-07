<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
            ->addSelect('p')
            ->join('e.organizer', 'o')
            ->join('e.state', 's')
            ->leftJoin('e.participants', 'p' )
            ->where("s.label != 'Activité historisée'")
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $id
     * @return Event
     * @throws EntityNotFoundException
     */
    public function getAllEventDataById($id){
        try {
            return $this->createQueryBuilder('e')
                ->andWhere('e.id = :id')
                ->setParameter('id', $id)
                ->addSelect('organizer')
                ->addSelect('state')
                ->addSelect('location')
                ->addSelect('city')
                ->addSelect('campus')
                ->addSelect('participants')
                ->join('e.organizer', 'organizer')
                ->join('e.state', 'state')
                ->join('e.location', 'location')
                ->join('location.city', 'city')
                ->join('e.campus', 'campus')
                ->leftJoin('e.participants', 'participants')
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException|NonUniqueResultException $e) {
            throw new EntityNotFoundException("La sortie demandée n'existe pas", 404);
        }
    }

    public function search($searchEvent, $user) {
        $query = $this->createQueryBuilder('e')
            ->addSelect('o')
            ->addSelect('s')
            ->addSelect('p')
            ->join('e.organizer', 'o')
            ->join('e.state', 's')
            ->leftJoin('e.participants', 'p' );
        if ($searchEvent->getCampus()) {
            $query->addSelect('c')
                ->join('e.campus', 'c')
                ->andWhere('e.campus = :campus')
                ->setParameter('campus', $searchEvent->getCampus());
        }
        if ($searchEvent->getName()) {
            $query->andWhere('e.name LIKE :name')
                ->setParameter('name', '%' . addcslashes($searchEvent->getName(), '%_') . '%');;
        }
        if ($searchEvent->getFrom()) {
            $query->andWhere('e.startDate >= :from')
                ->setParameter('from', $searchEvent->getFrom());
        }
        if ($searchEvent->getTo()) {
            $query->andWhere('e.startDate <= :to')
                ->setParameter('to', $searchEvent->getTo()->setTime(23, 59, 59));
        }
        if ($searchEvent->isOrganized()) {
            $query->andWhere('e.organizer = :organizer')
                ->setParameter('organizer', $user);
        }
        if ($searchEvent->isSubscribed()) {
            $query->andWhere(':participant MEMBER OF e.participants')
                ->setParameter('participant', $user);
        }
        if ($searchEvent->isNotSubscribed()) {
            $query->andWhere(':notParticipant NOT MEMBER OF e.participants')
                ->setParameter('notParticipant', $user);
        }
        if ($searchEvent->isOver()) {
            $query->andWhere('e.startDate < CURRENT_TIMESTAMP()');
        }

        return $query->getQuery()->getResult();
    }

    public function statesUpdate() {
        $expr = $this->getEntityManager()->getExpressionBuilder();

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

    /**
     * @param $id
     * @return Event
     * @throws EntityNotFoundException
     */
    public function findOrFail($id){
        $event = $this->find($id);
        if ($event === null) throw new EntityNotFoundException("La sortie demandé n'existe pas");
        return $event;
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
