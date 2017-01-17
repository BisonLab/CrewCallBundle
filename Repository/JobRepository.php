<?php

namespace CrewCallBundle\Repository;

use CrewCallBundle\Entity\Person;
use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 *
 */
class JobRepository extends \Doctrine\ORM\EntityRepository
{
    public function findUpcomingForPerson(Person $person, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $states = ExternalEntityConfig::getBookedStatesFor('Job');
        $qb = $this->_em->createQueryBuilder();
        $qb->select('j')
            ->from($this->_entityName, 'j')
            ->where('j.state in (:states)')
            ->andWhere("j.person = :person")
            ->setParameter('states', $states)
            ->setParameter('person', $person);
        return $qb->getQuery()->getResult();
    }

    public function findWishlistForPerson(Person $person, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $states = ExternalEntityConfig::getWishlistStatesFor('Job');
        $qb = $this->_em->createQueryBuilder();
        $qb->select('j')
            ->from($this->_entityName, 'j')
            ->where('j.state in (:states)')
            ->andWhere("j.person = :person")
            ->setParameter('states', $states)
            ->setParameter('person', $person);
        return $qb->getQuery()->getResult();
    }
}
