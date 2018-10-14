<?php

namespace CrewCallBundle\Lib\StateHandlers;

class Job
{
    private $em;
    private $sm;
    private $jobhandler;

    public function __construct($em, $sm)
    {
        $this->em = $em;
        $this->sm = $sm;
    }

    public function handle($job, $from, $to)
    {
        if ($to == "CONFIRMED") {
            // Create a message.
            $data = array(
                'event'  => $job->getEvent(),
                'person' => $job->getPerson(),
            );
            $this->sm->postMessage(array(
                'template' => 'confirm-sms',
                'template_data' => $data,
                'subject' => "Confirmation",
                'to_type' => "INTERNAL",
                'from_type' => "INTERNAL",
                'to' => $job->getPerson()->getUserName(),
                'message_type' => "PMSMS"
            ));
        }
    }
}
