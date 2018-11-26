<?php

namespace CrewCallBundle\Repository;

use CrewCallBundle\Entity\Person;
use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 *
 */
class JobRepository extends \Doctrine\ORM\EntityRepository
{
    /*
     * TODO: Add timeframe and default with from now
     */
    public function findJobsForPerson(Person $person, $options)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('j')
            ->from($this->_entityName, 'j')
            ->where("j.person = :person")
            ->setParameter('person', $person);

        if (isset($options['state'])) {
            $qb->andWhere('j.state = :state')
            ->setParameter('state', $options['state']);
        }

        if (isset($options['states'])) {
            $qb->andWhere('j.state in (:state)')
            ->setParameter('states', $options['states']);
        }

        if (isset($options['wishlist'])) {
            $states = ExternalEntityConfig::getWishlistStatesFor('Job');
            $qb->andWhere('j.state in (:state)')
            ->setParameter('states', $states);
        }

        if (isset($options['booked'])) {
            $states = ExternalEntityConfig::getBookedStatesFor('Job');
            $qb->innerJoin('j.shift', 's');
            $qb->andWhere('j.state in (:states)')
                ->setParameter('states', $states);
            if (!isset($options['from'])) {
                $from = new \DateTime();
                $qb->andWhere('s.start >= :from')
                    ->setParameter('from', $from);
            }
        }

        if (isset($options['old'])) {
            $qb->innerJoin('j.shift', 's');
            if (!isset($options['to'])) {
                $to = new \DateTime();
                $qb->andWhere('s.end >= :to')
                    ->setParameter('to', $to);
            }
        }

        if (isset($options['from']) || isset($options['to'])) {
            $qb->innerJoin('j.shift', 's');
            // Unless there are a set timeframe, use "from now".
            $from = new \DateTime();
            if (isset($options['from'])) {
                if ($options['from'] instanceof \DateTime )
                    $from = $options['from'];
                else
                    $from = new \DateTime($options['from']);
            }
            $qb->andWhere('s.start >= :from')
               ->setParameter('from', $from);

            if (isset($options['to'])) {
                if ($options['to'] instanceof \DateTime )
                    $to = $options['to'];
                else
                    $to = new \DateTime($options['to']);
                $qb->andWhere('s.end <= :to')
                   ->setParameter('to', $to);
            }
            $qb->orderBy('s.end', 'DESC');
        }
        return $qb->getQuery()->getResult();
    }

    /*
     * TODO: Add timeframe and default with from now
     */
    public function findByStateForPerson(Person $person, $options)
    {
        $state = $options['state'];
        $qb = $this->_em->createQueryBuilder();
        $qb->select('j')
            ->from($this->_entityName, 'j')
            ->where('j.state = :state')
            ->andWhere("j.person = :person")
            ->setParameter('state', $state)
            ->setParameter('person', $person);

        if (isset($options['from']) || isset($options['to'])) {
            $qb->innerJoin('j.shift', 's');
            // Unless there are a set timeframe, use "from now".
            $from = new \DateTime();
            if (isset($options['from'])) {
                if ($options['from'] instanceof \DateTime )
                    $from = $options['from'];
                else
                    $from = new \DateTime($options['from']);
            }
            $qb->andWhere('s.start >= :from')
               ->setParameter('from', $from);

            if (isset($options['to'])) {
                if ($options['to'] instanceof \DateTime )
                    $to = $options['to'];
                else
                    $to = new \DateTime($options['to']);
                $qb->andWhere('s.end <= :to')
                   ->setParameter('to', $to);
            }
        }
        $qb->orderBy('s.end', 'DESC');
        return $qb->getQuery()->getResult();
    }
}
