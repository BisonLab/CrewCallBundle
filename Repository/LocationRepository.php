<?php

namespace CrewCallBundle\Repository;

/**
 *
 */
class LocationRepository extends \Doctrine\ORM\EntityRepository
{
    /* This is very common for all repos. Could be in a trait aswell. */
    public function searchByField($field, $value, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('p')
            ->from($this->_entityName, 'p')
            ->where("lower(p." . $field . ") like ?1")
            ->orWhere("upper(p." . $field . ") like ?2")
            ->setParameter(1, '%' . mb_strtolower($value) . '%')
            ->setParameter(2, '%' . mb_strtoupper($value) . '%');

        return $qb->getQuery()->getResult();
    }
}
