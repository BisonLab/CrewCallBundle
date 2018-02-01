<?php

namespace CrewCallBundle\Repository;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 *
 */
class ShiftRepository extends \Doctrine\ORM\EntityRepository
{
    public function findUpcomingForFunctions(array $functions, $options = array())
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('s')
            ->from($this->_entityName, 's')
            ->where("s.function in (:functions)")
            ->setParameter('functions', $functions);

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

        // There are a few options here. Well, one for now, "booked".
        if (isset($options['booked'])) {
            $states = ExternalEntityConfig::getBookedStatesFor('Shift');
            $qb->andWhere('s.state in (:states)')
               ->setParameter('states', $states);
        }
        return $qb->getQuery()->getResult();
    }
}
