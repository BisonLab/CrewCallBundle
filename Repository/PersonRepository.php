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
        $found = new \Doctrine\Common\Collections\ArrayCollection();
        $qb = $this->_em->createQueryBuilder();
        $qb->select('p')
            ->from($this->_entityName, 'p')
            ->innerJoin('p.person_functions', 'pf')
            ->innerJoin('pf.function', 'f')
            ->where("f.function_type = :function_type")
            ->setParameter("function_type", $function_type);
        foreach ($qb->getQuery()->getResult() as $per) {
            $found->add($per);
        }

        $qb2 = $this->_em->createQueryBuilder();
        $qb2->select('p')
            ->from($this->_entityName, 'p')
            ->innerJoin('p.person_function_organizations', 'pf')
            ->innerJoin('pf.function', 'f')
            ->where("f.function_type = :function_type")
            ->setParameter("function_type", $function_type);
        foreach ($qb2->getQuery()->getResult() as $per) {
            if ($found->contains($per))
                continue;
            $found->add($per);
        }

        $qb3 = $this->_em->createQueryBuilder();
        $qb3->select('p')
            ->from($this->_entityName, 'p')
            ->innerJoin('p.person_function_locations', 'pf')
            ->innerJoin('pf.function', 'f')
            ->where("f.function_type = :function_type")
            ->setParameter("function_type", $function_type);
        foreach ($qb3->getQuery()->getResult() as $per) {
            if ($found->contains($per))
                continue;
            $found->add($per);
        }

        return $found;
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
