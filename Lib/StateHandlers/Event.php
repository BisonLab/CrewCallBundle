<?php

namespace CrewCallBundle\Lib\StateHandlers;

/*
 * In case of having to handle events.
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
        // Sigh, not working. This way to handle states is too limiting.
        if ($to == "READY") {
            $uow = $this->em->getUnitOfWork();
            foreach ($event->getShifts() as $shift) {
                $shift->setState("CLOSED");
                $meta = $this->em->getClassMetadata(get_class($shift));
                $uow->computeChangeSet($meta, $shift);
                $uow->computeChangeSets();
                $uow->recomputeSingleEntityChangeSet($meta, $shift);
            }
        }

        if ($to == "CONFIRMED") {
            $uow = $this->em->getUnitOfWork();
            foreach ($event->getChildren() as $child) {
                $child->setState('CONFIRMED');
                $meta = $this->em->getClassMetadata(get_class($child));
                $uow->computeChangeSet($meta, $child);
                $uow->computeChangeSets();
                $uow->recomputeSingleEntityChangeSet($meta, $child);
            }
            foreach ($event->getShifts() as $shift) {
                $shift->setState('OPEN');
                $meta = $this->em->getClassMetadata(get_class($shift));
                $uow->computeChangeSet($meta, $shift);
                $uow->computeChangeSets();
                $uow->recomputeSingleEntityChangeSet($meta, $shift);
            }
        }

        if ($to == "COMPLETED") {
            $uow = $this->em->getUnitOfWork();
            foreach ($event->getShifts() as $shift) {
                $shift->setState("CLOSED");
                $meta = $this->em->getClassMetadata(get_class($shift));
                $uow->computeChangeSet($meta, $shift);
                $uow->computeChangeSets();
                $uow->recomputeSingleEntityChangeSet($meta, $shift);
            }
        }
    }
}
