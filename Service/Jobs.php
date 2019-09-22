<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Shift;
use CrewCallBundle\Entity\Event;

class Jobs
{
    private $em;
    private $sakonnin;
    private $checks_event_cache = [];
    private $checks_shift_cache = [];

    public function __construct($em, $sakonnin)
    {
        $this->em = $em;
        $this->sakonnin = $sakonnin;
    }

    /*
     * Kinda not related to this, but kinda is aswell - functions.
     */

    /*
     * This is the reason the functions below exists.
     */
    public function checksForJob(Job $job)
    {
        $jcontext = [
            'base_type' => "CHECK",
            'order' => 'DESC',
            'system' => 'crewcall',
            'object_name' => 'job',
            'external_id' => $job->getId()
        ];
        return $this->sakonnin->getMessagesForContext($jcontext);
    }

    /*
     * Gotta have control on checks to check against.
     */
    public function checksForShift(Shift $shift)
    {
        if (!isset($this->checks_shift_cache[$shift->getId()])) {
            $checks = $this->checksForEvent($shift->getEvent());
            if ($epar = $shift->getEvent()->getParent()) {
                    $checks = array_merge($checks,
                        $this->checksForEvent($epar));
            }
            $scontext = [
                'base_type' => "CHECK",
                'order' => 'DESC',
                'system' => 'crewcall',
                'object_name' => 'shift',
                'external_id' => $shift->getId()
            ];
            if ($sc = $this->sakonnin->getMessagesForContext($scontext))
                $checks = array_merge($checks, $sc);
            
            $this->checks_shift_cache[$shift->getId()] = $checks;
        }
        return $this->checks_shift_cache[$shift->getId()];
    }

    public function checksForEvent(Event $event)
    {
        if (!isset($this->checks_event_cache[$event->getId()])) {
            $econtext = [
                'base_type' => "CHECK",
                'order' => 'DESC',
                'system' => 'crewcall',
                'object_name' => 'event',
                'external_id' => $event->getId()
            ];
            $checks = $this->sakonnin->getMessagesForContext($econtext);
            $this->checks_event_cache[$event->getId()] = $checks;
        }
        return $this->checks_event_cache[$event->getId()];
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

    public function jobsForPerson(Person $person, $options = array())
    {
        $jobs = $this->em->getRepository('CrewCallBundle:Job')
            ->findJobsForPerson($person, $options);
        $c = $this->checkOverlap($jobs);
        return $c;
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
        $options['open'] = true;
        $shifts = $this->em->getRepository('CrewCallBundle:Shift')
            ->findUpcomingForFunctions($functions, $options);

        foreach ($shifts as $sf) {
            // If not open for registration, don't.
            if (!$sf->isOpen())
                continue;
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

    /*
     * Annoying name, just couldn't come up with a better.
     */
    public function checkOverlapForPerson(Job $job, $options = array())
    {
        $job_repo = $this->em->getRepository('CrewCallBundle:Job');
        return $job_repo->checkOverlapForPerson($job, $options);
    }
}
