<?php

namespace CrewCallBundle\Repository;

/**
 *
 */
class PersonStateRepository extends \Doctrine\ORM\EntityRepository
{
    public function findByPerson($person, $options = [])
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('ps')
            ->from($this->_entityName, 'ps')
            ->where("ps.person = :person")
            ->setParameter("person", $person);

        // Between.
        if (isset($options['from_date']) && isset($options['to_date'])) {
            $from = new \DateTime($options['from_date']);
            $to   = new \DateTime($options['to_date']);
            $qb->andWhere('ps.from_date <= :to_date')
                ->setParameter('to_date', $to);
            $qb->andWhere('ps.to_date >= :from_date OR ps.to_date is null')
                ->setParameter('from_date', $from);
        // Not sure if these works. 50/50 and me..
        } elseif (isset($options['from_date'])) {
            $from = new \DateTime($options['from_date']);
            $qb->andWhere('ps.from_date <= :from_date')
                ->setParameter('from_date', $from);
        } elseif (isset($options['to_date'])) {
            $to = new \DateTime($options['to_date']);
            $qb->andWhere('ps.to_date <= :to_date OR ps.to_date is null')
                ->setParameter('to_date', $to);
        }
        $q = $qb->getQuery();
        $states = $q->getResult();
        return $states;
    }
}
