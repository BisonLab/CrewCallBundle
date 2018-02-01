<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Shift;

class Jobs
{
    private $em;

    public function __construct($em)
    {
        $this->em         = $em;
    }

    public function jobsForPerson(Person $person, $options = array())
    {
        // The lot, not really good since it will become HUGE, but useable if
        // we add filters/criterias, like a time interval.
        if (isset($options['all'])) {
            return $person->getJobs();
        } elseif (isset($options['upcoming'])) {
            return $this->em->getRepository('CrewCallBundle:Job')->findUpcomingForPerson($person);
        } elseif (isset($options['wishlist'])) {
            return $this->em->getRepository('CrewCallBundle:Job')->findWishlistForPerson($person);
        }
    }

    public function upcomingForPerson(Person $person, $options = array())
    {
        return $this->em->getRepository('CrewCallBundle:Job')
                     ->findUpcomingForPerson($person, $options);
    }

    public function opportunitiesForPerson(Person $person, $options = array())
    {
        // Should I cache or should I not?
        // Hopefully Doctrine does the job just as good, so I won't for now.
        $opportunities = new ArrayCollection();
        $jobshift = new ArrayCollection();
        $jobs = $this->jobsForPerson($person, array('upcoming' => true));
        foreach ($jobs as $job) {
            $jobshift->add($job->getShift());
        }

        // I'd better have a "getFunctions" on Person, but I don't like
        // that name, so I'll wait until I've found one I like.
        $functions = array();
        foreach ($person->getPersonFunctions() as $pf) {
            $functions[] = $pf->getFunction();
        }
        $shifts = $this->em->getRepository('CrewCallBundle:Shift')->findUpcomingForFunctions($functions, $options['booked'] = true);

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

    public function overlap(Shift $one, Shift $two)
    {
        // Why bother checking if it's the same? :=)
        if ($one === $two) return true;
        return (($one->getStart() <= $two->getEnd()) && ($one->getEnd() >= $two->getStart()));
    }
}
