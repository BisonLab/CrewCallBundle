<?php

namespace CrewCallBundle\Repository;

/**
 *
 */
class ShiftOrganizationRepository extends \Doctrine\ORM\EntityRepository
{
    /*
     * Find'em all, or fewer.
     * Blatantly nicked from the Job repo and I wish I could use the same code.
     */
    public function findJobs($options = [])
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('j')
            ->from($this->_entityName, 'j')
            ->innerJoin('j.shift', 's');

        // This only halfly works, since it doesen't get the children events.
        // Nesting more than one level of parent/child feels messy.
        // (At least until someone with more DQL-fu shows me an effective way)
        if (isset($options['events']) || isset($options['event_states'])) {
            $qb->innerJoin('s.event', 'e');
            if (isset($options['events'])) 
                $qb->andWhere('e.id in (:events)')
                ->setParameter('events', $options['events']);
            if (isset($options['event_states']))
                $qb->andWhere('e.state in (:event_states)')
                ->setParameter('event_states', $options['event_states']);
            // If there are no from or to set, set them far far away.
            // Easier than hacking the from date stuff at the end here.
            if (!isset($options['from']))
                $options['from'] = "2000-01-01";
            if (!isset($options['to']))
                $options['to'] = "3000-01-01";
        }

        if (isset($options['shift_states'])) {
            if (isset($options['shift_states']))
                $qb->andWhere('s.state in (:shift_states)')
                ->setParameter('shift_states', $options['shift_states']);
        }

        if (isset($options['functions'])) {
            if (isset($options['functions'])) 
                $qb->andWhere('s.function in (:functions)')
                ->setParameter('functions', $options['functions']);
        }

        if (isset($options['state'])) {
            $qb->andWhere('j.state = :state')
            ->setParameter('state', $options['state']);
        }

        if (isset($options['states'])) {
            $qb->andWhere('j.state in (:states)')
            ->setParameter('states', $options['states']);
        }

        if (isset($options['wishlist'])) {
            $states = ExternalEntityConfig::getWishlistStatesFor('Job');
            $qb->andWhere('j.state in (:state)')
            ->setParameter('states', $states);
        }

        if (isset($options['booked'])) {
            $states = ExternalEntityConfig::getBookedStatesFor('Job');
            $qb->andWhere('j.state in (:states)')
                ->setParameter('states', $states);
        }

        if (isset($options['past'])) {
            if (!isset($options['to'])) {
                $to = new \DateTime();
                $qb->andWhere('s.end <= :to')
                    ->setParameter('to', $to);
            }
        }

        // Unless there are a set timeframe, use "from now".
        $from = new \DateTime();
        // And here it can be overridden
        if (isset($options['from']) || isset($options['to'])) {
            if (isset($options['from'])) {
                if ($options['from'] instanceof \DateTime )
                    $from = $options['from'];
                else
                    $from = new \DateTime($options['from']);
            }
            if (isset($options['to'])) {
                if ($options['to'] instanceof \DateTime )
                    $to = $options['to'];
                else
                    $to = new \DateTime($options['to']);
                $qb->andWhere('s.start <= :to')
                   ->setParameter('to', $to);
            }
        }
        // Either the default or what's set above.
        $qb->andWhere('s.end >= :from')->setParameter('from', $from);
        $qb->orderBy('s.start', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
