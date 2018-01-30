<?php

namespace CrewCallBundle\Repository;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 *
 */
class ShiftRepository extends \Doctrine\ORM\EntityRepository
{
    public function findUpcomingForFunctions(array $functions)
    {
        $states = ExternalEntityConfig::getActiveStatesFor('Shift');
        $qb = $this->_em->createQueryBuilder();
        $qb->select('s')
            ->from($this->_entityName, 's')
            ->where('s.state in (:states)')
            ->andWhere("s.function in (:functions)")
            ->setParameter('states', $states)
            ->setParameter('functions', $functions);
        return $qb->getQuery()->getResult();
    }
}
