<?php

namespace CrewCallBundle\Repository;

/**
 *
 */
class JobLogRepository extends \Doctrine\ORM\EntityRepository
{
    public function checkOverlapForPerson($joblog)
    {
dump($joblog);
        $person = $joblog->getJob()->getPerson();
        $from = $joblog->getIn();
        $to = $joblog->getOut();
        $qb = $this->_em->createQueryBuilder();
        $qb->select('jl')
            ->from($this->_entityName, 'jl')
            ->innerJoin('jl.job', 'j')
            ->where("j.person = :person")
            ->andWhere('jl.in <= :to')
            ->andWhere('jl.out >= :from')
            ->setParameter('person', $person)
            ->setParameter('to', $to)
            ->setParameter('from', $from);
        return $qb->getQuery()->getResult();
    }
}
