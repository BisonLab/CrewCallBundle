<?php

namespace CrewCallBundle\Repository;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 *
 */
class ShiftFunctionRepository extends \Doctrine\ORM\EntityRepository
{
    public function findUpcomingForFunctions(array $functions, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $states = ExternalEntityConfig::getActiveStatesFor('Shift');
        $qb = $this->_em->createQueryBuilder();
        $qb->select('sf')
            ->from($this->_entityName, 'sf')
            ->innerJoin('sf.shift', 's')
            ->where('s.state in (:states)')
            ->andWhere("sf.function in (:functions)")
            ->setParameter('states', $states)
            ->setParameter('functions', $functions);
        return $qb->getQuery()->getResult();
    }
}
