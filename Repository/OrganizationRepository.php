<?php

namespace CrewCallBundle\Repository;

/**
 *
 */
class OrganizationRepository extends \Doctrine\ORM\EntityRepository
{
    use \BisonLab\CommonBundle\Entity\ContextRepositoryTrait;

    /* This is very common for all repos. Could be in a trait aswell. */
    public function searchByField($field, $value)
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
