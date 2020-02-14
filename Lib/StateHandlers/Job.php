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

    public function handle(\CrewCallBundle\Entity\Job $job, $from, $to)
    {
        if ($to == "CONFIRMED" || $to == "ASSIGNED") {
            // Create a message.
            $data = array(
                'job'    => $job,
                'event'  => $job->getEvent(),
                'person' => $job->getPerson(),
                'function' => $job->getFunction(),
            );
            if ($to == "CONFIRMED")
                $template = 'confirm-sms';
            if ($to == "ASSIGNED")
                $template = 'assigned-sms';
            $this->sm->postMessage([
                'template' => $template,
                'template_data' => $data,
                'subject' => "Confirmation",
                'to_type' => "INTERNAL",
                'from_type' => "INTERNAL",
                'message_type' => "PM"
            ],
            [
                'system' => 'crewcall',
                'object_name' => 'person',
                'external_id' => $job->getPerson()->getId(),
            ]);
        }
    }
}
