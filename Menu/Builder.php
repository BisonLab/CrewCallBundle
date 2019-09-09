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
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $eventsmenu = $menu->addChild('Events', array('route' => 'event_index'));

            // Todo: Use system_role, if they can log in (ROLE_USER and
            // ROLE_ADMIN), they should be here.
            $crewmenu = $menu->addChild("Crew",
                array('route' => 'person_function_type',
                'routeParameters' => array('function_type' => "SKILL")));
            $menu->addChild('Organizations', array('route' => 'organization_index'));
            $menu->addChild('Locations', array('route' => 'location_index'));

            $adminmenu = $menu->addChild('Admin Stuff', array('route' => ''));
            foreach (ExternalEntityConfig::getTypesFor('FunctionEntity', 'FunctionType') as $ftname => $ftarr) {
                $adminmenu->addChild('Manage ' . $ftarr['plural'],
                    array('route' => 'function_index',
                    'routeParameters' => array('function_type' => $ftname)));
            }
            $adminmenu->addChild('Report generator', array('route' => 'reports'));
            $adminmenu->addChild('Mail and SMS templates',
                array('route' => 'sakonnintemplate_index'));

            $sakonnin = $this->container->get('sakonnin.messages');
            // Do we have a message for the front page?
            $fpnl_type = $sakonnin->getMessageType('Front page not logged in');

            if (count($fpnl_type->getMessages()) > 0) {
                $fpm = $fpnl_type->getMessages()[0];
                $adminmenu->addChild('Edit login page message',
                    array('route' => 'message_edit',
                    'routeParameters' => array('id' => $fpm->getId())));
            } else {
                $adminmenu->addChild('Add login page message',
                    array('route' => 'message_new',
                    'routeParameters' => array('message_type' => $fpnl_type)));
            }

            $fpl_type = $sakonnin->getMessageType('Front page logged in');

            if (count($fpl_type->getMessages()) > 0) {
                $fpm = $fpl_type->getMessages()[0];
                $adminmenu->addChild('Edit front page announcement',
                    array('route' => 'message_edit',
                    'routeParameters' => array('id' => $fpm->getId())));
            } else {
                $adminmenu->addChild('Add front page announcement',
                    array('route' => 'message_new',
                    'routeParameters' => array('message_type' => $fpl_type)));
            }

            $adminmenu->addChild('Message Types',
                array('route' => 'messagetype'));
            foreach (ExternalEntityConfig::getTypesFor('FunctionEntity', 'FunctionType') as $ftname => $ftarr) {
                $adminmenu->addChild("People with " . $ftarr['plural'],
                    array('route' => 'person_function_type',
                    'routeParameters' => array('all' => true, 'function_type' => $ftname)));
            }
            if ($this->container->getParameter('allow_registration')) {
                $adminmenu->addChild('Applicants', array('route' => 'person_applicants'));
            }
            // Not sure I need it, reapply in custom if you need it.
            // $adminmenu->addChild('Add person', array('route' => 'person_new'));

            $adminmenu->addChild('Playfront', array('route' => 'frontplay'));
            $adminmenu->addChild('Mobilefront', array('uri' => '/public/userfront/'));
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


        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $username = $user->getUserName();

        $menu = $this->common_builder->userMenu($factory, $options);
        $usermenu = $menu[$username];
        $usermenu->addChild('My Jobs', array('route' => 'user_me'));
        $usermenu->addChild('My Calendar', array('route' => 'user_me_calendar'));

        if ($this->container->getParameter('enable_personal_messaging')) {
            $usermenu = $this->sakonnin_builder->messageMenu($factory, $options);
            if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
                $pmmenu = $usermenu['Messages']->addChild('Write PM and send SMS', array('uri' => '#'));
                $pmmenu->setLinkAttribute('onclick', 'createPmMessage("PMSMS")');
            } else {
                $usermenu['Messages']->removeChild('Message History');
            }
        }

        // For local customized additions to the main menu.
        if ($this->custom_builder
                && method_exists($this->custom_builder, "userMenu"))
            $menu = $this->custom_builder->userMenu($factory, $options);
        return $menu;
    }
}
