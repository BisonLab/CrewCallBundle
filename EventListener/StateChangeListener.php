<?php

namespace CrewCallBundle\EventListener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

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
        return $this->state_handler->handleStateChange(
            $eventArgs->getEntity(),
            // Null or just empty string?
            null,
            $eventArgs->getEntity()->getState()
            );
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        // If state not changed, why bother?
        if (!$eventArgs->hasChangedField('state'))
            return true;

        return $this->state_handler->handleStateChange(
            $eventArgs->getEntity(),
            $eventArgs->getOldValue('state'),
            $eventArgs->getNewValue('state')
            );
    }

    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
    }
}
