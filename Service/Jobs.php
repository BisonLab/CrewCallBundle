<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\Person;

class Jobs
{
    private $em;

    public function __construct($em)
    {
        $this->em         = $em;
    }

    public function jobsForPerson(Person $person, $options = array())
    {
        if (isset($options['all'])) {
            return $person->getJobs();
        } elseif (isset($options['upcoming'])) {
            return $this->em->getRepository('CrewCallBundle:Job')->findUpcomingForPerson($person);
        } elseif (isset($options['wishlist'])) {
            return $this->em->getRepository('CrewCallBundle:Job')->findWishlistForPerson($person);
        }
    }

    public function opportunitiesForPerson(Person $person, $options = array())
    {
        // Should I cache or should I not?
        // Hopefully Doctrine does the job just as good, so I won't for now.
        $opportunities = new ArrayCollection();
        $jobshiftfunctions = new ArrayCollection();
        $jobs = $person->getJobs();
        foreach ($jobs as $job) {
            $jobshiftfunctions->add($job->getShiftFunction());
        }
        foreach ($person->getPersonFunctions() as $pf) {
            foreach ($pf->getFunction()->getShiftFunctions() as $sf) {
                if (!$jobshiftfunctions->contains($sf))
                    $opportunities->add($sf);
            }
        }
        return $opportunities;
    }
}
