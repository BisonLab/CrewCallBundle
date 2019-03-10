<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Shift;

class Jobs
{
    private $em;
    private $sakonnin;
    private $shiftcache = [];

    public function __construct($em, $sakonnin)
    {
        $this->em = $em;
        $this->sakonnin = $sakonnin;
    }

    /*
     * Admin functions.
     */

    /*
     * Crew chief functions. (When we have a crew chief)
     */

    /*
     * Person specific functions.
     */
    /*
     * For creating an array which is serializebale and suiteable for the
     * user/worker frontend (Rather customize this here than use the
     * serializer on job, which would end up with way too much data unless I
     * really messed around with it. And I'll leave that for the admin
     * frontend.
     */
    public function jobsForPersonAsArray(Person $person, $options = array())
    {
        $jobs = $this->em->getRepository('CrewCallBundle:Job')
            ->findJobsForPerson($person, $options);
        // Just walk throug it once, alas overlap check here aswell.
        $lastjob = null;
        $lastarr = null;
        $checked = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($jobs as $job) {
            $arr = [
                'name' => (string)$job,
                'id' => $job->getId(),
            ];
            $shiftarr = $this->getShiftArr($job->getShift());
            $arr = array_merge($arr, $shiftarr);

            if ($lastjob && $this->overlap($job->getShift(), $lastjob->getShift())) {
                $arr['overlap'] = true;
                $checked->last()['overlap'] = true;
            } else {
                $arr['overlap'] = false;
            }
            $checked->add($arr);
            $lastjob = $job;
        }
        return $checked->toArray();
    }

    public function jobsForPerson(Person $person, $options = array())
    {
        $jobs = $this->em->getRepository('CrewCallBundle:Job')
            ->findJobsForPerson($person, $options);
        $c = $this->checkOverlap($jobs);
        return $c;
    }

    public function opportunitiesForPersonAsArray(Person $person, $options = array())
    {
        $opps = [];
        foreach ($this->opportunitiesForPerson($person, $options) as $o) {
            $arr = [
                'name' => (string)$o,
                'id' => $o->getId(),
            ];
            $opps[] = array_merge($arr, $this->getShiftArr($o));
        }
        return $opps;
    }

    public function opportunitiesForPerson(Person $person, $options = array())
    {
        // Should I cache or should I not?
        // Hopefully Doctrine does the job just as good, so I won't for now.
        $opportunities = new ArrayCollection();
        $jobshift = new ArrayCollection();
        $jobs = $this->jobsForPerson($person, $options);
        foreach ($jobs as $job) {
            $jobshift->add($job->getShift());
        }

        // I'd better have a "getFunctions" on Person, but I don't like
        // that name, so I'll wait until I've found one I like.
        $functions = array();
        foreach ($person->getPersonFunctions() as $pf) {
            $functions[] = $pf->getFunction();
        }
        $options['booked'] = true;
        $shifts = $this->em->getRepository('CrewCallBundle:Shift')
            ->findUpcomingForFunctions($functions, $options);

        foreach ($shifts as $sf) {
            // Already in jobs?
            if (!$jobshift->contains($sf)) {
                // Check if we have time overlap between already booked job and
                // the opportunities.
                /*
                 * Gotta decide if I want to do this or not. Not for now since
                 * it ends up removing opportunities also when the existing job
                 * is just in the wishlist.
                foreach ($jobshift as $jsf) {
                    if ($this->overlap($jsf->getShift(), $sf->getShift()))
                        continue 2;
                }
                 */
                // And it's still here.
                $opportunities->add($sf);
            }
        }
        return $opportunities;
    }

    public function checkOverlap($jobs)
    {
        $last = null;
        $checked = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($jobs as $job) {
            if ($last && $this->overlap($job->getShift(), $last->getShift())) {
                $job->setOverlap(true);
                $last->setOverlap(true);
            }
            $checked->add($job);
            $last = $job;
        }
        return $checked;
    }

    public function overlap(Shift $one, Shift $two)
    {
        // Why bother checking if it's the same? :=)
        if ($one === $two) return true;
        return (($one->getStart() <= $two->getEnd()) && ($one->getEnd() >= $two->getStart()));
    }

    // Private for now
    private function getShiftArr(Shift $shift)
    {
        // So, what do we need here? To be continued..
        if (!isset($this->shiftcache[$shift->getId()])) {
            $event = $shift->getEvent();
            $eventparent = $event->getParent();
            $location = $event->getLocation();
            $organization = $event->getOrganization();
            $confirm_notes = [];
            $checks = [];
            $scnc = [
                'system' => 'crewcall',
                'object_name' => 'shift',
                'message_type' => 'ConfirmNote',
                'external_id' => $shift->getId(),
            ];
            foreach ($this->sakonnin->getMessagesForContext($scnc) as $c) {
                $confirm_notes[] = [
                    'id' => $c->getId(),
                    'subject' => $c->getSubject(),
                    'body' => $c->getBody()
                    ];
            }
            $sccc = [
                'system' => 'crewcall',
                'object_name' => 'shift',
                'message_type' => 'ConfirmCheck',
                'external_id' => $shift->getId(),
            ];
            foreach ($this->sakonnin->getMessagesForContext($sccc) as $c) {
                $checks[] = [
                    'id' => $c->getId(),
                    'type' => (string)$c->getMessageType(),
                    'body' => $c->getBody()
                    ];
            }
            if ($eventparent) {
                $all_events = $eventparent->getChildren()->toArray();
                $all_events[] = $eventparent;
            } else {
                $all_events = [$event];
            }
            foreach ($all_events as $e) {
                $ecnc = [
                    'system' => 'crewcall',
                    'object_name' => 'event',
                    'message_type' => 'ConfirmNote',
                    'external_id' => $e->getId(),
                ];
                foreach ($this->sakonnin->getMessagesForContext($ecnc) as $c) {
                    $confirm_notes[] = [
                        'id' => $c->getId(),
                        'subject' => $c->getSubject(),
                        'body' => $c->getBody()];
                }
                $eccc = [
                    'system' => 'crewcall',
                    'object_name' => 'event',
                    'message_type' => 'ConfirmCheck',
                    'external_id' => $event->getId(),
                ];
                foreach ($this->sakonnin->getMessagesForContext($eccc) as $c) {
                    $checks[] = [
                        'id' => $c->getId(),
                        'type' => (string)$c->getMessageType(),
                        'body' => $c->getBody()
                        ];
                }
            }
            $arr = [
                'event' => [
                    'name' => (string)$event,
                    'id' => $event->getId(),
                    'location' => [
                        'name' => $location->getName(),
                        'address' => (string)$location->getAddress()
                    ],
                    'organization' => [
                        'name' => $organization->getName(),
                    ],
                ],
                'shift' => [
                    'name' => (string)$shift,
                    'id' => $shift->getId(),
                    'function' => (string)$shift->getFunction(),
                    'start_date' => $shift->getStart()->format("Y-m-d H:i"),
                    'start_string' => $shift->getStart()->format("d M H:i"),
                    'end_date' => $shift->getEnd()->format("Y-m-d H:i"),
                    'end_string' => $shift->getEnd()->format("d M H:i"),
                ],
                'checks' => $checks,
                'confirm_notes' => $confirm_notes
            ];
            $this->shiftcache[$shift->getId()] = $arr;
        }
        return $this->shiftcache[$shift->getId()];
    }
}
