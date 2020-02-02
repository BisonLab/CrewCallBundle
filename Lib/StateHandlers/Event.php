<?php

namespace CrewCallBundle\Lib\StateHandlers;

/*
 * For now this handles the trickle down of states on the shifts based on the
 * (main) event.
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
                if ($uow->isEntityScheduled($shift)) {
                    $meta = $this->em->getClassMetadata(get_class($shift));
                    $uow->computeChangeSet($meta, $shift);
                    $uow->computeChangeSets();
                    $uow->recomputeSingleEntityChangeSet($meta, $shift);
                }
            }
        }

        if ($to == "CONFIRMED") {
            $uow = $this->em->getUnitOfWork();
            foreach ($event->getChildren() as $child) {
                if ($child->getState() == "CONFIRMED")
                    continue;
                $child->setState('CONFIRMED');
                if ($uow->isEntityScheduled($child)) {
                    $meta = $this->em->getClassMetadata(get_class($child));
                    $uow->computeChangeSet($meta, $child);
                    $uow->computeChangeSets();
                    $uow->recomputeSingleEntityChangeSet($meta, $child);
                }
            }
            foreach ($event->getShifts() as $shift) {
                if ($shift->getState() == "OPEN")
                    continue;
                $shift->setState('OPEN');
                if ($uow->isEntityScheduled($shift)) {
                    $meta = $this->em->getClassMetadata(get_class($shift));
                    $uow->computeChangeSet($meta, $shift);
                    $uow->computeChangeSets();
                    $uow->recomputeSingleEntityChangeSet($meta, $shift);
                }
            }
        }

        if ($to == "COMPLETED") {
            $uow = $this->em->getUnitOfWork();
            foreach ($event->getShifts() as $shift) {
                if ($shift->getState() == "CLOSED")
                    continue;
                $shift->setState("CLOSED");
                $meta = $this->em->getClassMetadata(get_class($shift));
                $uow->computeChangeSet($meta, $shift);
                $uow->computeChangeSets();
                if ($uow->isEntityScheduled($shift))
                    $uow->recomputeSingleEntityChangeSet($meta, $shift);
            }
        }
    }
}
