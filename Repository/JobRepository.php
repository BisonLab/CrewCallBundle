<?php

namespace CrewCallBundle\Repository;

use CrewCallBundle\Entity\Person;
use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 *
 */
class JobRepository extends \Doctrine\ORM\EntityRepository
{
    /*
     * TODO: Add timeframe and default with from now
     */
    public function findByStateForPerson(Person $person, $options)
    {
        $state = $options['state'];
        $qb = $this->_em->createQueryBuilder();
        $qb->select('j')
            ->from($this->_entityName, 'j')
            ->where('j.state = :state')
            ->andWhere("j.person = :person")
            ->setParameter('state', $state)
            ->setParameter('person', $person);
        if (isset($options['from']) || isset($options['to'])) {
            // Do that TODO:
        }
        return $qb->getQuery()->getResult();
    }

    public function findBookedUpcomingForPerson(Person $person)
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

    public function findWishlistForPerson(Person $person)
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

    /*
     * TODO: Add timeframe and default with from now
     */
    public function findUpcomingForPerson(Person $person)
    {
        $states = ExternalEntityConfig::getBookedStatesFor('Job');
        $qb = $this->_em->createQueryBuilder();
        $qb->select('j')
            ->from($this->_entityName, 'j')
            ->where("j.person = :person")
            ->setParameter('person', $person);
        return $qb->getQuery()->getResult();
    }
}
