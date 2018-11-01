<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\JobLog;
use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Shift;

class JobLogs
{
    private $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function getJobLogsForPerson(Person $person, $options = array())
    {
        // This has to do a lot more. It should use criterias to narrow down
        // the amount of joblog entries. Maybe even do this with a join!
        // Right now I'll just return the summary and all joblogs there is.
        $summary = array(
            'week'      => 0,
            'l7days'    => 0,
            'month'     => 0,
            'year'      => 0,
            'last_year' => 0,
            'total'     => 0,
        );

        /*
         * strtotime is not to be trusted and "first day of " will only work
         * on month and nothing else.
         * Alas, this is a pretty diverse piece of code.
         */
        $first_of_week      = new \DateTime('00:00');
        $first_of_week->modify('this week');
        $l7days      = new \DateTime('00:00');
        $l7days->modify('-7 days');
        $first_of_month     = new \DateTime();
        $first_of_month->modify('first day of this month');
        $first_of_year      = new \DateTime(date('Y-01-01'));
        $first_of_last_year = new \DateTime(date('Y-01-01'));
        $first_of_last_year->modify('-1 year');
        $joblogs = array();
        foreach ($person->getJobs() as $job) {
            foreach ($job->getJobLogs() as $jl) {
                // TODO: Check state. I guess "COMPLETED" is the one to use.
                $joblogs[] = $jl;
                $in  = $jl->getIn();
                $out = $jl->getOut();
                // DateTime interval does NOT work. Stupidly enough.
                $minutes = ($out->getTimeStamp() - $in->getTimeStamp()) / 60;
                $summary['total'] += $minutes;

                if ($out < $first_of_year && $out > $first_of_last_year) {
                    $summary['last_year'] += $minutes;
                }
                if ($out > $first_of_week) {
                    $summary['week'] += $minutes;
                }
                if ($out > $l7days) {
                    $summary['l7days'] += $minutes;
                }
                if ($out > $first_of_month) {
                    $summary['month'] += $minutes;
                }
                if ($out > $first_of_year) {
                    $summary['year'] += $minutes;
                }
            }
        }
        $summary['week_hours']      = $this->mToHm($summary['week']);
        $summary['l7days_hours']    = $this->mToHm($summary['l7days']);
        $summary['month_hours']     = $this->mToHm($summary['month']);
        $summary['year_hours']      = $this->mToHm($summary['year']);
        $summary['last_year_hours'] = $this->mToHm($summary['last_year']);
        $summary['total_hours']     = $this->mToHm($summary['total']);
        return array('joblogs' => $joblogs, 'summary' => $summary);
    }
    
    private function mToHm($minutes)
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        return $h . ":" . str_pad($m, 2, "0", STR_PAD_LEFT);
    }
}
