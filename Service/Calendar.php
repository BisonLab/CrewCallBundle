<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\Shift;
use CrewCallBundle\Entity\ShiftOrganization;
use CrewCallBundle\Entity\Event;

/*
 * This thignie will convert events, shiftfs, and more with start and
 * end to FullCalendar - json and ical objects.
 */

class Calendar
{
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function toIcal($frog)
    {
        if ($frog instanceof Event) {
            $cal = $this->eventToCal($frog);
        } elseif ($frog instanceof Shift) {
            $cal = $this->shiftToCal($frog);
        } elseif ($frog instanceof Job) {
            $cal = $this->jobToCal($frog);
        } else {
            throw new \InvalidArgumentException("Could not do anything useful with "
                . get_class($frog));
        }
        // TODO: Configurable domain.
        $vCalendar = new \Eluceo\iCal\Component\Calendar('CrewCall');
        $vEvent = new \Eluceo\iCal\Component\Event();
        $vEvent->setSummary($cal['title']);
        $vEvent->setDtStart($cal['start']);
        if ($cal['end'])
            $vEvent->setDtEnd($cal['end']);

        $vCalendar->addComponent($vEvent);
        return $vCalendar->render();
    }

    public function toFullCalendarArray($frogs)
    {
        $arr = array();
        foreach ($frogs as $frog) {
            $arr[] = $this->calToFullCal($frog);
        }
        return $arr;
    }

    /*
     * We need: (but can do with more)
     *  - Id
     *  - Start
     *  - End
     *  - Title
     *  - Allday (true if it is)
     *  - Url (But I'm not sure this is the right place, and we do need two of'em. iCal and to the event/shift(function) itself.
     *
     * I can consider adding colouring of items based on state and/or length here. 
     * Or make it a customizeable thinge with another service.
     *
     */
   
    public function calToFullCal($frog)
    {
        if ($frog instanceof Event) {
            $cal = $this->eventToCal($frog);
        } elseif ($frog instanceof Shift) {
            $cal = $this->shiftToCal($frog);
        } elseif ($frog instanceof Job) {
            $cal = $this->jobToCal($frog);
        } else {
            throw new \InvalidArgumentException("Could not do anything useful with "
                . get_class($frog));
        }
        $fc['id'] = $cal['id'];
        $fc['title'] = $cal['title'];
        $fc['start'] = $cal['start']->format("Y-m-d\TH:i:sP");
        if ($cal['end'])
            $fc['end'] = $cal['end']->format("Y-m-d\TH:i:sP");
        else
            $fc['end'] = null;
        /*
        $fc['className'] = $cal[''];
        $fc['rendering'] = $cal[''];
        $fc['constraint'] = $cal[''];
        $fc['source'] = $cal[''];
        */
        if (isset($cal['url']))
            $fc['url'] = $cal['url'];
        if (isset($cal['color']))
            $fc['color'] = $cal['color'];
        if (isset($cal['backgroundColor']))
            $fc['backgroundColor'] = $cal['backgroundColor'];
        if (isset($cal['borderColor']))
            $fc['borderColor'] = $cal['borderColor'];
        if (isset($cal['textColor']))
            $fc['textColor'] = $cal['textColor'];
        if (isset($cal['allDay']))
            $fc['allDay'] = $cal['allDay'];
        else
            $fc['allDay'] = false;
        $fc['overlap'] = false;
        $fc['editable'] = false;
        $fc['startEditable'] = false;
        $fc['durationEditable'] = false;
        $fc['resourceEditable'] = false;
        return $fc;
    }

    public function eventToCal(Event $event)
    {
        $c = array();
        $c['id'] = $event->getId();
        $c['title'] = $event->getName();
        $c['start'] = $event->getStart();
        $c['end'] = $event->getEnd();
        return $c;
    }

    public function jobToCal(Job $job)
    {
        $c = $this->shiftToCal($job->getShift());
        $c['title'] = $job->getFunction()->getName();
        if ($job->isBooked()) {
            $c['color'] = "green";
            $c['textColor'] = "white";
        } else {
            $c['color'] = "orange";
            $c['textColor'] = "black";
        }
        $c['url'] =  $this->router->generate('user_job_calendar_item', array('id' => $job->getId()));
        return $c;
    }

    public function shiftToCal(Shift $shift)
    {
        $c = array();
        $c['id'] = $shift->getId();
        $c['title'] = "Shift";
        $c['start'] = $shift->getStart();
        $c['end'] = $shift->getEnd();
        $c['title'] = $shift->getFunction()->getName();
        return $c;
    }
}