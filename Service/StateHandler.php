<?php

namespace CrewCallBundle\Service;

use CrewCallBundle\Entity\Event;
use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Job;

class StateHandler
{
    private $em;
    private $sm;
    private $jobhandler;

    public function __construct($em, $sm)
    {
        $this->em = $em;
        $this->sm = $sm;

        if (class_exists('CustomBundle\Lib\StateHandlers\Job')) {
            $this->jobhandler = new \CustomBundle\Lib\StateHandlers\Job($em, $sm);
        } else {
            $this->jobhandler = new \CrewCallBundle\Lib\StateHandlers\Job($em, $sm);
        }
    }

    public function handleStateChange($entity, $from, $to)
    {
        if ($entity instanceof Job) {
            $this->jobhandler->handle($entity, $from, $to);
        }
        return;
    }
}
