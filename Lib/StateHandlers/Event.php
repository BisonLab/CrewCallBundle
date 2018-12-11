<?php

namespace CrewCallBundle\Lib\StateHandlers;

/*
 * In case of having to handle events.
 * (OK, made this before realising it was shifts I had to deal with now..)
 * But it will be used on completing an event.
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

    public function handle($job, $from, $to)
    {
    }
}
