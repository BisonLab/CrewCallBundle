<?php

namespace CrewCallBundle\EventListener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

class StateChangeListener
{
    private $state_handler;

    public function __construct($state_handler)
    {
        $this->state_handler = $state_handler;
    }

    /*
     * This is preInsert!
     * Using false as old value is done to let the state handler know that this
     * is an insert and that it does not have to mess with UnitOfWork.
     * If I mess with that on insert I'll end up in tears, or rather a 500.
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if (method_exists($entity, 'getState')) {
            return $this->state_handler->handleStateChange(
                $entity,
                false,
                $eventArgs->getEntity()->getState()
                );
        }
        return true;
    }

    /*
     * So, why not preUpdate?
     * Not enough access to the UnitOfWork API they say.
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            foreach ($uow->getEntityChangeSet($entity) as $field => $values) {
                if ($field != "state")
                    continue;
                $this->state_handler->handleStateChange(
                    $entity,
                    $values[0],
                    $values[1]
                    );
            }
        }
    }
}
