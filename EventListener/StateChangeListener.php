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

    // This is the more or less preInsert.
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if (method_exists($entity, 'getState')) {
            return $this->state_handler->handleStateChange(
                $entity,
                // Null or just empty string?
                null,
                $eventArgs->getEntity()->getState()
                );
        }
        return true;
    }

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
