<?php

namespace CrewCallBundle\Lib\Sakonnin;

use BisonLab\SakonninBundle\Entity\MessageContext;

/*
 */

class SmsHandler
{
    protected $container;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    /*
     * Returning null on missing code and wrong sender instead of throwing
     * exceptions. Rather have silence than 500 errors.
     */
    public function execute($options = array())
    {
        $sm = $this->container->get('sakonnin.messages');
        $codeword = $this->container->getParameter('sakonnin.sms')['smscode'];

        $message = $options['message'];
        $body = str_replace($codeword, "", strtolower($message->getBody()));

        if (!preg_match("/\s(\w{6})\s/", $body, $umatch))
            return null;
        $ucode = $umatch[1];

        // Gotta find the Job related to the code
        $em = $this->container->get('doctrine.orm.crewcall_entity_manager');

        // Does the job exist?
        if (!$job = $em->getRepository('CrewCallBundle:Job')->findOneBy(['ucode' => $ucode])) {
            return null;
        }
        // The sender matches the job owner?
        $number_length = $this->container->getParameter('sakonnin.sms')['national_number_lenght'];
        $person = $job->getPerson();
        $snum = substr($message->getFrom(), $number_length * -1);
        $pnum = substr($person->getMobilePhoneNumber(), $number_length * -1);

        if ($snum != $pnum)
            return null;

        // So, what will we be doing then?
        // For now, just look for "Confirm".
        if (preg_match("/CONFIRM/", strtoupper($body)) && $job->getState() == "ASSIGNED") {
            $job->setState("CONFIRMED");
            // Gonna tie the message to the job object.
            $smmanager = $sm->getDoctrineManager();
            $mc = new MessageContext();
            $mc->setOwner($message);
            $mc->setSystem('crewcall');
            $mc->setObjectName('job');
            $mc->setExternalId($job->getId());
            $smmanager->persist($mc);
            $smmanager->flush();
            $em->flush();
        }
        return true;
    }
}
