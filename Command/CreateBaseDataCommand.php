<?php

namespace CrewCallBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Entity\SakonninTemplate;

use CrewCallBundle\Entity\FunctionEntity;
use CrewCallBundle\Entity\Organization;

class CreateBaseDataCommand extends ContainerAwareCommand
{
    /*
     * Replacing the base data insert in Sakonnin.
     */
    private $message_types = array(
       'Email' => array(
                'description' => 'Emails'
                ),
       'Messages' => array(
                'description' => 'Messaging'
                ),
       'Notes' => array(
                'description' => 'Notes'
                ),
       'Announcements' => array(
                'description' => 'Announcements'
                ),
        'PersonNote' => array(
            'parent' => 'Notes',
            'base_type' => 'NOTE',
            'security_model' => 'ADMIN_RW_USER_R',
            'description' => "Note about a person the person and admins can read."),
        'AdminNote' => array(
            'parent' => 'Notes',
            'base_type' => 'NOTE',
            'security_model' => 'ADMIN_ONLY',
            'description' => "Note only admins can read"),
        'ConfirmNote' => array(
            'parent' => 'Notes',
            'base_type' => 'NOTE',
            'security_model' => 'ALL_READ',
            'description' => "Note a crewmember has to ACK before asking for a specific job"),
        'PMSMS' => array(
            'parent' => 'Messages',
            'base_type' => 'MESSAGE',
            'security_model' => 'PRIVATE',
            'forward_function' => 'smscopy',
            'description' => "PM with SMS copy"),
        'SMS' => array(
            'parent' => 'Messages',
            'base_type' => 'MESSAGE',
            'security_model' => 'PRIVATE',
            'forward_function' => 'smscodehandle',
            'description' => "Receiving SMSes"),
        'BULKSMS' => array(
            'parent' => 'Messages',
            'base_type' => 'MESSAGE',
            'security_model' => 'PRIVATE',
            'forward_function' => 'smscopy',
            'description' => "Send SMSes to a bunch of people"),
        'BULKALL' => array(
            'parent' => 'Messages',
            'base_type' => 'MESSAGE',
            'security_model' => 'PRIVATE',
            'forward_function' => 'pmsmsmailcopy',
            'description' => "Messages sent in all ways possible to a bunch of people"),
        'Checks' => array(
            'base_type' => 'CHECK',
            'description' => 'Checkbox items'),
        'ConfirmCheck' => array(
            'parent' => 'Checks',
            'base_type' => 'CHECK',
            'security_model' => 'ALL_READ',
            'description' => "Checkbox you must confirm"),
        'InformCheck' => array(
            'parent' => 'Checks',
            'base_type' => 'CHECK',
            'security_model' => 'ALL_READ',
            'description' => "Checkbox for added information"),
        'TODO' => array(
            'parent' => 'Checks',
            'base_type' => 'CHECK',
            'security_model' => 'ADMIN_ONLY',
            'description' => "For the TODO list"),
        'Admin Wall' => array(
            'parent' => 'Notes',
            'base_type' => 'NOTE',
            'security_model' => 'ADMIN_ONLY',
            'description' => "Admin wall notes"),
        'Front page logged in' => array(
            'parent' => 'Announcements',
            'base_type' => 'NOTE',
            'security_model' => 'ALL_READ',
            'description' => "Front page Announcement for logged in users"
            ),
        'Front page not logged in' => array(
            'parent' => 'Announcements',
            'base_type' => 'NOTE',
            'security_model' => 'ALL_READ',
            'description' => "Front page Announcement for not yet0logged in users"
            ),
    );

    protected function configure()
    {
        $this
            ->setName('once:create-base-data')
            ->setDescription('Creates the data we need in the data base for using this application.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $output->writeln('First, message types.');
        $this->_messageTypes($input, $output);
        $output->writeln('OK Done.');

        $internal_organization_config = $this->getContainer()->getParameter('internal_organization');
        $org = new Organization();
        $org->setName($internal_organization_config['name']);
        $org->setState("ACTIVE");
        $this->em->persist($org);
        $role = new FunctionEntity();
        $role->setName($internal_organization_config['default_role']);
        $role->setFunctionType('ROLE');
        $role->setState("VISIBLE");
        $this->em->persist($role);
        $this->em->flush();

        // And then, add assign-sms and confirm-sms sakonnin templates
        $asms = new SakonninTemplate();
        $asms->setName('assign-sms');
        $asms->setTemplate('Hello {{ person.firstname }}, please confirm the following job: {{ event.name }}. At: {{ event.location.name }}, {{ job.start | date("d.m.y") }} {{ job.start | date("H:i") }}, estimated finish time {{ job.end | date("d.m.y H:i") }}. Function: {{ function }}. Log into Crew Call to confirm');
        $asms->setLangCode('en');

        $csms = new SakonninTemplate();
        $csms->setName('confirm-sms');
        $csms->setTemplate('Thank you for confirming {{ event.name }} at {{ event.location.name }}, {{ job.start | date("d.m.yH:i")}}, estimated finish time {{ job.end | date("d.m.yH:i") }}. Function: {{ function }}. Have a nice day.');
        $csms->setLangCode('en');

        $this->sakonnin_em->persist($asms);
        $this->sakonnin_em->persist($csms);
        $this->sakonnin_em->flush();
    }

    private function _messageTypes(InputInterface $input, OutputInterface $output)
    {
        $this->sakonnin_em = $this->getContainer()->get('doctrine')->getManager('sakonnin');
        $this->mt_repo    = $this->sakonnin_em
                ->getRepository('BisonLabSakonninBundle:MessageType');

        foreach ($this->message_types as $name => $type) {
            // Handling a parent.
            $parent = null;
            if (isset($type['parent']) && !$parent = $this->_findMt($type['parent'])) {
                error_log("Could not find the group " . $type['parent']);
                return false;
            }

            $mt = new MessageType();

            $mt->setName($name);
            if (isset($type['base_type']))
                $mt->setBaseType($type['base_type']);
            if (isset($type['description']))
                $mt->setDescription($type['description']);
            if (isset($type['callback_function']))
                $mt->setCallbackFunction($type['callback_function']);
            if (isset($type['callback_type']))
                $mt->setCallbackType($type['callback_type']);
            if (isset($type['forward_function']))
                $mt->setForwardFunction($type['forward_function']);
            $mt->setExpungeMethod($type['expunge_method'] ?? "DELETE");
            $mt->setExpireMethod($type['expire_method'] ?? "DELETE");
            $mt->setSecurityModel($type['security_model'] ?? "PRIVATE");

            $this->sakonnin_em->persist($mt);
            if ($parent) {
                $output->writeln("Setting parent " 
                    . $parent->getName() . " on " . $mt->getName());
                $parent->addChild($mt);
                $this->sakonnin_em->persist($parent);
            }

            if ($mt)
                $this->mt_cache[$mt->getName()] = $mt;
            $output->writeln("Created " . $mt->getName());
        }
        $this->sakonnin_em->flush();
    }

    private function _findMt($name) {
        if (isset($this->mt_cache[$name]))
            return $this->mt_cache[$name];

        $mt = $this->mt_repo->findOneByName($name);
        if ($mt)
            $this->mt_cache[$name] = $mt;
        else
            return null;

        return $this->mt_cache[$name];
    }
}
