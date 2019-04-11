<?php

namespace CrewCallBundle\Repository;

/**
 *
 */
class PersonRepository extends \Doctrine\ORM\EntityRepository
{
    use \BisonLab\CommonBundle\Entity\ContextRepositoryTrait;

    public function findByFunctionType($function_type)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('p')
            ->from($this->_entityName, 'p')
            ->innerJoin('p.person_functions', 'pf')
            ->innerJoin('pf.function', 'f')
            ->where("f.function_type = :function_type")
            ->setParameter("function_type", $function_type);
        $pfs = $qb->getQuery()->getResult();

        $qb2 = $this->_em->createQueryBuilder();
        $qb2->select('p')
            ->from($this->_entityName, 'p')
            ->innerJoin('p.person_function_organizations', 'pf')
            ->innerJoin('pf.function', 'f')
            ->where("f.function_type = :function_type")
            ->setParameter("function_type", $function_type);

        return new \Doctrine\Common\Collections\ArrayCollection(
            array_merge($pfs, $qb2->getQuery()->getResult()));
    }

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
