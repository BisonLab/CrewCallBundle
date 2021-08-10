<?php
namespace CrewCallBundle\Controller;

use CrewCallBundle\Lib\ExternalEntityConfig;

trait CommonControllerFunctions
{
    /*
     * In case of more filters, send request.
     */
    public function filterPeople($people, $options)
    {
        $em = $this->getDoctrine()->getManager();
        $job_repo = $em->getRepository('CrewCallBundle:Job');

        $select_grouping = $options['select_grouping'] ?? null;
        $crew_only = $options['crew_only'] ?? false;
        $on_date = $options['on_date'] ?? null;

        // If all, return all.
        if (!$crew_only && $select_grouping == 'all') {
            return $people;
        }

        $filtered = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($people as $p) {
            if ($crew_only && !$p->isCrew()) {
                continue;
            }
            if ($select_grouping == "no_crew" && $p->isCrew()) {
                continue;
            }
            if ($select_grouping == "all") {
                if (!$filtered->contains($p))
                    $filtered->add($p);
            }
            if ($on_date) {
                if ($select_grouping == 'all_active') {
                    if (!$filtered->contains($p))
                        $filtered->add($p);
                }
                if ($select_grouping == "available") {
                    if ($p->isOccupied(['date' => $on_date]))
                        continue;
                    if (!$filtered->contains($p))
                        $filtered->add($p);
                    continue;
                }

                // Now filter based on select_group
                $jobs = $job_repo->findJobsForPerson($p, [
                        'from' => $on_date,
                        'to' => $on_date,
                        ]);
                $add_person = false;
                foreach ($jobs as $j) {
                    switch($select_grouping) {
                        case 'booked':
                            if (in_array($j->getState(),
                                ExternalEntityConfig::getBookedStatesFor('Job')))
                                    $add_person = true;
                            break;
                        case 'interested':
                            if ($j->getState() == "INTERESTED")
                                $add_person = true;
                            break;
                        case 'assigned':
                            if ($j->getState() == "ASSIGNED")
                                $add_person = true;
                            break;
                        case 'confirmed':
                            if ($j->getState() == "CONFIRMED")
                                $add_person = true;
                            break;
                    }
                }
                if ($add_person)
                    if (!$filtered->contains($p))
                        $filtered->add($p);
            // And if no on_date set:
            } else {
                if ($select_grouping == 'all_active') {
                    if (!in_array($p->getState(),
                            ExternalEntityConfig::getActiveStatesFor('Person')))
                        continue;
                }
                if (!$filtered->contains($p))
                    $filtered->add($p);
            }
        }
        return $filtered;
    }
}
