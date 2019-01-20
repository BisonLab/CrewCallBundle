<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\Event;
use CrewCallBundle\Entity\Shift;

class Events
{
    private $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function cloneEvent(Event $orig, Event $clone)
    {
        // First, find the difference in time between original and clone.
        $diff = $orig->getStart()->diff($clone->getStart());
        $nend = clone($orig->getEnd());
        $clone->setEnd($nend->add($diff));
        
        foreach ($orig->getShifts() as $shift) {
            $ns = new Shift();
            $ns->setAmount($shift->getAmount());
            $ns->setFunction($shift->getFunction());
            $nsstart = clone($shift->getStart());
            $ns->setStart($nsstart->add($diff));
            $nsend = clone($shift->getEnd());
            $ns->setEnd($nsend->add($diff));
            $clone->addShift($ns);
        }

        foreach ($orig->getChildren() as $child) {
            $new_child = new Event();
            // What about the name?
            $new_child->setName($child->getName());
            $new_child->setDescription($child->getDescription());
            $new_child->setOrganization($child->getOrganization());
            $new_child->setLocation($child->getLocation());
            $cstart = clone($child->getStart());
            $new_child->setStart($cstart->add($diff));
            $nc = $this->cloneEvent($child, $new_child);
            $clone->addChild($nc);
        }
        // $this->em->persist($clone);
        // $this->em->flush($clone);
        
        return $clone;
    }
}
