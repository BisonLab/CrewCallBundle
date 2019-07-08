<?php

namespace CrewCallBundle\Lib\Reports;

use Doctrine\ORM\EntityRepository;
use BisonLab\ReportsBundle\Lib\Reports\ReportsInterface;
use BisonLab\ReportsBundle\Lib\Reports\CommonReportFunctions;
use DabaruBundle\Lib\ExternalEntityConfig;

/*
 */

class WorkLog extends CommonReportFunctions
{
    protected $container;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    // All fixed reports shall be hydrated as arrays.
    public function runFixedReport($config = null)
    {
        $em = $this->getManager();
        $jobservice = $this->container->get('crewcall.jobs');
        $jobloghandler = $this->container->get('crewcall.joblogs');

        $where_added = false;
        $qb = $em->createQueryBuilder();
        $qb->select('e')
           ->from('CrewCallBundle\Entity\Event', 'e');

        // These does not really work any more..
        // Need fixing somehow.
        if (isset($config['event']) && $config['event']) {
                $qb->where('e.id = :event');
            $qb->setParameter('event', $config['event']);
            $where_added = true;
        }

        $header = [
            'Event name',
            'Function',
            'Shift Start',
            'Shift End',
            'Name',
            'Time worked, minutes',
            'Time worked, hours',
            'First in',
            'Last out',
        ];

        /*
         * Feel free to define this as lazyness.
         */
        $result = $qb->getQuery()->iterate();
        // This one can be really big..
        $data = array();
        foreach ($result as $itemres) {
            $event = $itemres[0];
            foreach ($event->getAllShifts() as $shift) {
                foreach ($shift->getJobs() as $job) {
                    $arr = [];
                    $arr[] = $event->getName(); 
                    $arr[] = (string)$shift->getFunction(); 
                    $arr[] = $shift->getStart()->format("Y-m-d H:i"); 
                    $arr[] = $shift->getEnd()->format("H:i"); 
                    $arr[] = $job->getPerson()->getFullName();
                    $minutes = 0;
                    $first = null;
                    $last = null;
                    foreach ($job->getJobLogs() as $joblog) {
                        if (!$first) $first = $joblog->getIn();
                        $minutes += ($joblog->getOut()->getTimeStamp() - $joblog->getIn()->getTimeStamp()) / 60;
                        $last = $joblog->getOut();
                    }
                    if ($minutes > 0) {
                        $arr[] = $minutes;
                        $h = floor($minutes / 60);
                        $m = $minutes % 60;
                        $arr[] = $h . ":" . str_pad($m, 2, "0", STR_PAD_LEFT);
                        $arr[] = $first->format("Y-m-d H:i");
                        $arr[] = $last->format("Y-m-d H:i");
                        $data[] = $arr;
                    }
                }
            }
        }

        return ['data' => $data, 'header' => $header];
    }
}
