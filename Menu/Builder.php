<?php

namespace CrewCallBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use CrewCallBundle\Lib\ExternalEntityConfig;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $custom_builder = null;
    private $sakonnin_builder = null;
    private $common_builder = null;

    public function __construct()
    {
        if (class_exists('CustomBundle\Menu\Builder')) {
            $this->custom_builder = new \CustomBundle\Menu\Builder();
        }
        if (class_exists('BisonLab\SakonninBundle\Menu\Builder')) {
            $this->sakonnin_builder = new \BisonLab\SakonninBundle\Menu\Builder();
        }
        if (class_exists('BisonLab\CommonBundle\Menu\Builder')) {
            $this->common_builder = new \BisonLab\CommonBundle\Menu\Builder();
        }
    }

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Dashboard', array('route' => 'homepage'));
        $menu->addChild('My Jobs', array('route' => 'user_me'));
        $menu->addChild('My Calendar', array('route' => 'user_me_calendar'));

        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $eventsmenu = $menu->addChild('Events');
            $eventsmenu->addChild('List', array('route' => 'event_index'));
            $eventsmenu->addChild('List old events', array('route' => 'event_index', 'routeParameters' => array('past' => 'true')));
            $eventsmenu->addChild('Add new event', array('route' => 'event_new'));
            $eventsmenu->addChild('Calendar', array('route' => 'event_calendar'));

            $peoplemenu = $menu->addChild('People');
            $em = $this->container->get('doctrine')->getManager();
            $peoplemenu->addChild('All', array('route' => 'person_index'));
            foreach (ExternalEntityConfig::getTypesFor('FunctionEntity', 'FunctionType') as $ftname => $ftarr) {
                // Spot the ugliness.
                $peoplemenu->addChild($ftarr['label'] . "s",
                    array('route' => 'person_function_type',
                    'routeParameters' => array('function_type' => $ftname)));
            }
            if ($this->container->getParameter('allow_registration')) {
                $peoplemenu->addChild('Applicants', array('route' => 'person_applicants'));
            }
            $peoplemenu->addChild('Add person', array('route' => 'person_new'));
            $menu->addChild('Organizations', array('route' => 'organization_index'));
            $menu->addChild('Locations', array('route' => 'location_index'));

            $adminmenu = $menu->addChild('Admin Stuff', array('route' => ''));
            $adminmenu->addChild('Functions', array('route' => 'function_index'));
            $adminmenu->addChild('Report generator', array('route' => 'reports'));
            $adminmenu->addChild('Mail and SMS templates',
                array('route' => 'sakonnintemplate_index'));
            $sakonnin = $this->container->get('sakonnin.messages');
            $amt = $sakonnin->getMessageType('Announcements');
            $adminmenu->addChild('Announcements',
                array('route' => 'message_messagetype',
                'routeParameters' => array('id' => $amt->getId())));
            $adminmenu->addChild('Message Types',
                array('route' => 'messagetype'));
            $menu->addChild('Jobs view', array('route' => 'jobsview_index'));
        }
        $options['menu']      = $menu;
        $options['container'] = $this->container;
        // For local additions to the main menu.
        if ($this->custom_builder
                && method_exists($this->custom_builder, "mainMenu"))
            $menu = $this->custom_builder->mainMenu($factory, $options);

        // Temporary, but have to be able to go to the existing CRUD.
        return $menu;
    }

    public function userMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        // Yeah, I'd call this a hack too.
        $options['menu']      = $menu;
        $options['container'] = $this->container;

        $menu = $this->common_builder->userMenu($factory, $options);
        if ($this->container->getParameter('enable_personal_messaging')) {
            $menu = $this->sakonnin_builder->messageMenu($factory, $options);
            if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
                $pmmenu = $menu['Messages']->addChild('Write PM and send SMS', array('uri' => '#'));
                $pmmenu->setLinkAttribute('onclick', 'createPmMessage("PMSMS")');
            } else {
                $menu['Messages']->removeChild('Message History');
            }
        }

        // For local customized additions to the main menu.
        if ($this->custom_builder
                && method_exists($this->custom_builder, "userMenu"))
            $menu = $this->custom_builder->userMenu($factory, $options);
        return $menu;
    }
}
