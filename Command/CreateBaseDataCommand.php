<?php

namespace CrewCallBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BisonLab\SakonninBundle\Entity\MessageType;

class CreateBaseDataCommand extends ContainerAwareCommand
{
    private $message_types = array(
        'PersonNote' => array(
            'parent' => 'Notes',
            'security_model' => 'ADMIN_ONLY',
            'description' => "Note about a person"),
        'OrganizationNote' => array(
            'parent' => 'Notes',
            'security_model' => 'ADMIN_ONLY',
            'description' => "Note about an organization"),
        'PMSMS' => array(
            'parent' => 'Messages',
            'security_model' => 'PRIVATE',
            'forward_function' => 'smscopy',
            'description' => "PM with SMS copy"),
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
            if (isset($type['description']))
                $mt->setDescription($type['description']);
            if (isset($type['callback_function']))
                $mt->setCallbackFunction($type['callback_function']);
            if (isset($type['callback_type']))
                $mt->setCallbackType($type['callback_type']);
            if (isset($type['forward_function']))
                $mt->setForwardFunction($type['forward_function']);
            if (isset($type['security_model']))
                $mt->setSecurityModel($type['security_model']);
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
