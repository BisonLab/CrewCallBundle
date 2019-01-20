<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\Shift;
use CrewCallBundle\Entity\ShiftOrganization;
use CrewCallBundle\Entity\Event;
use CrewCallBundle\Entity\PersonState;

/*
 * This thignie will convert events, shiftfs, and more with start and
 * end to FullCalendar - json and ical objects.
 */

class Calendar
{
    private $router;
    private $summarizer;
    private $user;

    public function __construct($router, $summarizer)
    {
        $this->router = $router;
        $this->summarizer = $summarizer;
    }

    public function toIcal($frog)
    {
        if ($frog instanceof Event) {
            $cal = $this->eventToCal($frog);
        } elseif ($frog instanceof Shift) {
            $cal = $this->shiftToCal($frog);
        } elseif ($frog instanceof Job) {
            $cal = $this->jobToCal($frog);
        } elseif ($frog instanceof PersonState) {
            $cal = $this->personStateToCal($frog);
        } else {
            throw new \InvalidArgumentException("Could not do anything useful with "
                . get_class($frog));
        }
        if (!$cal) return null;
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

    public function toFullCalendarArray($frogs, $user = null)
    {
        $this->user = $user;
        $arr = array();
        foreach ($frogs as $frog) {
            if ($cal = $this->calToFullCal($frog))
                $arr[] = $cal;
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
        } elseif ($frog instanceof PersonState) {
            $cal = $this->personStateToCal($frog);
        } else {
            throw new \InvalidArgumentException("Could not do anything useful with "
                . get_class($frog));
        }
        if (!$cal) return null;
        $fc['id'] = $cal['id'];
        $fc['title'] = $cal['title'];
        $fc['content'] = $cal['content'] ?? '';
        $fc['popup_title'] = $cal['popup_title'] ?? '';
        $fc['popup_content'] = $cal['popup_content'] ?? '';
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
        // Pretty complex, but it does use the ID to make sure we use the same
        // colour on the same (sub) event.
        $phi = 0.618033988749895;
        $phi = (1 + sqrt(5))/2;
        $id =  $event->getId();
        if ($event->getParent()) $id = $event->getParent()->getId();
        $id = $id * 3.14;
        $n = $id * $phi - floor($id * $phi);
        $hue = floor($n * 256);
        $col = $this->hslToRgb( $hue, 0.5, 0.7 );

        $c = array();
        $c['id'] = $event->getId();
        $c['title'] = $event->getName();
        $c['start'] = $event->getStart();
        $c['end'] = $event->getEnd();
        $c['color'] = "#" . $col;
        $c['textColor'] = "black";
        $c['content'] = 
              'What: ' . (string)$event . "\n"
            . 'Where: ' . (string)$event->getLocation() . "\n";

        $url =  $this->router->generate('event_show', 
            array('id' => $event->getId()));
        $c['popup_title'] = (string)$event;

        // Should I do summary maybe?
        $c['popup_content'] = preg_replace("/\n/", "<br />"
            , $c['content']) . '<br><a href="'
            . $url  . '">Go to event</a>';

        return $c;
    }

    public function jobToCal(Job $job)
    {
        $c = $this->shiftToCal($job->getShift());
        $c['title'] = (string)$job->getFunction();
        if ($job->isBooked()) {
            $c['color'] = "green";
            $c['textColor'] = "white";
        } else {
            $c['color'] = "orange";
            $c['textColor'] = "black";
        }
        // For the text in the ical calendar thingie.
        $c['content'] = 
              'What: ' . (string)$job->getEvent() . "\n"
            . 'Work: ' . (string)$job->getFunction() . "\n"
            . 'Where: ' . (string)$job->getLocation() . "\n";

        $url =  $this->router->generate('user_job_calendar_item', 
            array('id' => $job->getId()));
        $c['ical_url'] = $url;
        // For a popover in the internal calendar.
        $c['popup_title'] = (string)$job->getFunction() . " at "
            . (string)$job->getLocation();

        /*
         * No need to "Put in my calendar" if it's not you.
         * And we may even want a completely different text.
         */
        if ($this->user == $job->getPerson()) {
            $c['popup_content'] = preg_replace("/\n/", "<br />"
                , $c['content']) . '<br><a href="'
                . $url  . '">Put in my calendar</a>';
        } else {
            $c['popup_content'] = preg_replace("/\n/", "<br />"
                , $c['content']);
        }
        return $c;
    }

    public function shiftToCal(Shift $shift)
    {
        $c = array();
        $c['id'] = $shift->getId();
        $c['start'] = $shift->getStart();
        $c['end'] = $shift->getEnd();
        $c['title'] = (string)$shift->getFunction();
        return $c;
    }

    public function personStateToCal(PersonState $ps)
    {
        if ($ps->getState() == "ACTIVE") return null;
        $c = array();
        $c['id'] = $ps->getId();
        $c['start'] = $ps->getFromDate();
        if (!$ps->getToDate())
            $td = new \DateTime("first day of next year");
        else
            $td = $ps->getToDate();
        $c['end'] = $td;
        $c['title'] = (string)$ps->getState();
        return $c;
    }

    // Nicked from https://gist.github.com/brandonheyer/5254516
    public function hslToRgb( $h, $s, $l )
    {
        $r; 
        $g; 
        $b;
        $c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
        $x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
        $m = $l - ( $c / 2 );
        if ( $h < 60 ) {
            $r = $c;
            $g = $x;
            $b = 0;
        } else if ( $h < 120 ) {
            $r = $x;
            $g = $c;
            $b = 0;         
        } else if ( $h < 180 ) {
            $r = 0;
            $g = $c;
            $b = $x;                    
        } else if ( $h < 240 ) {
            $r = 0;
            $g = $x;
            $b = $c;
        } else if ( $h < 300 ) {
            $r = $x;
            $g = 0;
            $b = $c;
        } else {
            $r = $c;
            $g = 0;
            $b = $x;
        }
        $r = floor(( $r + $m ) * 255);
        $g = floor(( $g + $m ) * 255);
        $b = floor(( $b + $m ) * 255);
        $wcol = str_pad(dechex(round($r)), 2, "0", STR_PAD_LEFT);
        $wcol .= str_pad(dechex(round($g)), 2, "0", STR_PAD_LEFT);
        $wcol .= str_pad(dechex(round($b)), 2, "0", STR_PAD_LEFT);
        return $wcol;
        return array( floor( $r ), floor( $g ), floor( $b ) );
    }
}
