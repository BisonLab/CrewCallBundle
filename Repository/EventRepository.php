<?php

namespace CrewCallBundle\Repository;

/**
 *
 */
class EventRepository extends \Doctrine\ORM\EntityRepository
{
    /*
     * Find'em all, or fewer.
     */
    public function findEvents($options = [])
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('e')
            ->from($this->_entityName, 'e');

        if (isset($options['parents_only'])) {
            $qb->andWhere('e.parent is null');
        }

        if (isset($options['state'])) {
            $qb->andWhere('e.state = :state')
            ->setParameter('state', $options['state']);
        }

        if (isset($options['states'])) {
            $qb->andWhere('e.state in (:state)')
            ->setParameter('states', $options['states']);
        }

        if (isset($options['booked'])) {
            $states = ExternalEntityConfig::getBookedStatesFor('Event');
            $qb->andWhere('e.state in (:states)')
                ->setParameter('states', $states);
        }

        $order = "ASC";
        if (isset($options['past'])) {
            $qb->andWhere('e.end < :today')
               ->setParameter('today', new \DateTime(),
                    \Doctrine\DBAL\Types\Type::DATETIME);
            $order = "DESC";
            // And we have to set from to something in the past unless it's 
            // set already.
            if (!isset($options['from']))
                $options['from'] = '2019-01-01';
        }

        if (isset($options['future'])) {
            $today = new \DateTime();
            $qb->andWhere('e.end > :yesterday')
                ->setParameter('yesterday', new \DateTime('yesterday'),
                    \Doctrine\DBAL\Types\Type::DATETIME);
        }

        if (isset($options['ongoing'])) {
            $qb->andWhere('e.end >= :today_start')
               ->andWhere('e.start <= :today_end')
               ->setParameter('today_start', new \DateTime("01:00"),
                    \Doctrine\DBAL\Types\Type::DATETIME)
               ->setParameter('today_end', new \DateTime("23:59"),
                    \Doctrine\DBAL\Types\Type::DATETIME);
        }

        // Unless there are a set timeframe, use "from now".
        $from = new \DateTime();
        // And here it can be overridden
        if (isset($options['from']) || isset($options['to'])) {
            if (isset($options['from'])) {
                if ($options['from'] instanceof \DateTime )
                    $from = $options['from'];
                else
                    $from = new \DateTime($options['from']);
            }
            if (isset($options['to'])) {
                if ($options['to'] instanceof \DateTime )
                    $to = $options['to'];
                else
                    $to = new \DateTime($options['to']);
                $qb->andWhere('e.start <= :to')
                   ->setParameter('to', $to);
            }
        }
        // Either the default or what's set above.
        $qb->andWhere('e.end >= :from')->setParameter('from', $from);
        $qb->orderBy('e.start', $order);
        if (isset($options['limit'])) {
            $qb->setMaxResults($options['limit']);
        }
        return $qb->getQuery()->getResult();
    }

    /* This is very common for all repos. Could be in a trait aswell. */
    public function searchByField($field, $value)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('e')
            ->from($this->_entityName, 'e')
            ->where("lower(e." . $field . ") like ?1")
            ->orWhere("upper(e." . $field . ") like ?2")
            ->setParameter(1, '%' . mb_strtolower($value) . '%')
            ->setParameter(2, '%' . mb_strtoupper($value) . '%');

        return $qb->getQuery()->getResult();
    }
}
