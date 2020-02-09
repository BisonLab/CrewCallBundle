<?php

namespace CrewCallBundle\Lib\StateHandlers;

/*
 * For now this handles the trickle down of states on the shifts based on the
 * (main) event.
 *
 * If "false" looks odd it's behcause it's a hack.
 * It's explained in the EventListener.
 * TL;DR It handles the difference between insert and update.
 */
class Event
{
    private $em;
    private $sm;

    public function __construct($em, $sm)
    {
        $this->em = $em;
        $this->sm = $sm;
    }

    public function handle(\CrewCallBundle\Entity\Event $event, $from, $to)
    {
        if ($to == "READY") {
            $uow = $this->em->getUnitOfWork();
            foreach ($event->getShifts() as $shift) {
                if ($shift->getState() == "CLOSED")
                    continue;
                $shift->setState("CLOSED");
                if ($from === false)
                    continue;
                $this->em->persist($shift);
                $meta = $this->em->getClassMetadata(get_class($shift));
                $uow->computeChangeSet($meta, $shift);
                $uow->computeChangeSets();
                $uow->recomputeSingleEntityChangeSet($meta, $shift);
            }
        }

        if ($to == "CONFIRMED") {
            $uow = $this->em->getUnitOfWork();
            foreach ($event->getChildren() as $child) {
                if ($child->getState() == "CONFIRMED")
                    continue;
                $child->setState('CONFIRMED');
                if ($from === false)
                    continue;
                $meta = $this->em->getClassMetadata(get_class($child));
                $uow->computeChangeSet($meta, $child);
                $uow->computeChangeSets();
                $uow->recomputeSingleEntityChangeSet($meta, $child);
            }
            foreach ($event->getShifts() as $shift) {
                if ($shift->getState() == "OPEN")
                    continue;
                $shift->setState('OPEN');
                if ($from === false)
                    continue;
                $meta = $this->em->getClassMetadata(get_class($shift));
                $uow->computeChangeSet($meta, $shift);
                $uow->computeChangeSets();
                $uow->recomputeSingleEntityChangeSet($meta, $shift);
            }
        }

        if ($to == "COMPLETED") {
            $uow = $this->em->getUnitOfWork();
            foreach ($event->getShifts() as $shift) {
                if ($shift->getState() == "CLOSED")
                    continue;
                $shift->setState("CLOSED");
                if ($from === false)
                    continue;
                $meta = $this->em->getClassMetadata(get_class($shift));
                $uow->computeChangeSet($meta, $shift);
                $uow->computeChangeSets();
                $uow->recomputeSingleEntityChangeSet($meta, $shift);
            }
        }
    }
}
