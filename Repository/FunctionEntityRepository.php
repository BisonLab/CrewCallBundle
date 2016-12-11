<?php

namespace CrewCallBundle\Repository;

use Doctrine\ORM\Query\ResultSetMapping;

/**
 *
 */
class FunctionEntityRepository extends \Doctrine\ORM\EntityRepository
{
    public function searchByField($field, $value, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        if ($field == 'attributes') {
            if (is_array($value)) {
                $afield = strtolower($value[0]);
                $avalue = $value[1];
            } else {
                list($afield, $avalue) = explode(":", $value);
            }
            $rsm = new ResultSetMapping;
            $rsm->addEntityResult('CrewCallBundle\Entity\FunctionEntity', 'cf');
            $rsm->addFieldResult('cf', 'id', 'id');
            $sql = "select id from crewcall_function where attributes->>'" . $afield . "'='" . $avalue . "';";
            $query = $this->_em->createNativeQuery($sql, $rsm);
            $ids = $query->getResult();
            if ($ids) {
                // Have to clear the result cache so we can get a complete
                // entity and not one with just the ID we asked for.
                // (I'm lazy and not botheres with adding all fields in the
                // select.
                $this->_em->clear();
                // Gotta find the whole object then.
                return $this->find($ids[0]->getId());
            }
            return null;
        } else {
            $qb = $this->_em->createQueryBuilder();
            $qb->select('f')
                ->from($this->_entityName, 'f')
                ->where("lower(f." . $field . ") like ?1")
                ->orWhere("upper(f." . $field . ") like ?2")
                ->setParameter(1, '%' . mb_strtolower($value) . '%')
                ->setParameter(2, '%' . mb_strtoupper($value) . '%');
            return $qb->getQuery()->getResult();
        }
    }
}
