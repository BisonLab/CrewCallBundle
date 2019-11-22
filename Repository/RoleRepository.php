<?php

namespace CrewCallBundle\Repository;

/**
 *
 */
class RoleRepository extends \Doctrine\ORM\EntityRepository
{
    public function findAll()
    {
        return $this->findBy(array(), array('name' => 'ASC'));
    }

    public function findNamesWithPeopleCount()
    {
        $query = $this->_em->createQuery('SELECT r.id, re.name, count(pr.id) as people FROM ' . $this->_entityName . ' r JOIN r.person_roles pr GROUP BY r.id');
        return $result = $query->getResult();
    }
}
