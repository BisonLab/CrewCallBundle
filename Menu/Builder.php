<?php

namespace CrewCallBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use CrewCallBundle\Lib\ExternalEntityConfig;

class Builder implements ContainerAwareInterface
{
    use \BisonLab\CommonBundle\Menu\StylingTrait;
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
                array('route' => 'crew_index',
                    'routeParameters' => array('select_grouping' => 'all_active')));
            $menu->addChild('Organizations', array('route' => 'organization_index'));
            $menu->addChild('Locations', array('route' => 'location_index'));

            $adminmenu = $menu->addChild('Admin Stuff', array('route' => ''));
            $adminmenu->addChild('Manage Functions',
                array('route' => 'function_index'));
            $adminmenu->addChild('Manage Roles',
                array('route' => 'role_index'));
            $adminmenu->addChild('Report generator', array('route' => 'reports'));

            $sakonnin = $this->container->get('sakonnin.messages');
            // Do we have a message for the front page?
            $fpnl_type = $sakonnin->getMessageType('Front page not logged in');
;
            $router = $this->container->get('router');
            if (count($fpnl_type->getMessages()) > 0) {
                $fpm = $fpnl_type->getMessages()[0];
                $elpm = $adminmenu->addChild('Edit login page message', array('uri' => "#"));
                $uri = $router->generate('message_edit', array('access' => 'ajax', 'id' => $fpm->getId(), 'reload_after_post' => true));
                $elpm->setLinkAttribute('onClick', "return openCcModal('" . $uri . "', 'Edit login page message');");
            } else {
                $alpm = $adminmenu->addChild('Add login page message',
                    array('uri' => '#'));
                $uri = $router->generate('message_new', array('access' => 'ajax', 'message_type' => $fpnl_type->getId(), 'reload_after_post' => true));
                $alpm->setLinkAttribute('onClick', "return openCcModal('" . $uri . "', 'Add login page message');");
            }

            $adminmenu->addChild("People with Roles",
                array('route' => 'person_role'));
            $adminmenu->addChild("People with Functions",
                array('route' => 'person_function'));
            if ($this->container->getParameter('allow_registration')) {
                $adminmenu->addChild('Applicants', array('route' => 'person_applicants'));
            }
            // Not sure I need it, reapply in custom if you need it.
            // $adminmenu->addChild('Add person', array('route' => 'person_new'));
            $adminmenu->addChild('Mail and SMS templates',
                array('route' => 'sakonnintemplate_index'));
            $adminmenu->addChild('Message Types',
                array('route' => 'messagetype'));

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

        $menu = $this->styleMenuBootstrap($menu, $options);
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
                $pmmenu->setLinkAttribute('onclick', 'createPmMessage("PM")');
            } else {
                $usermenu['Messages']->removeChild('Message History');
            }
        }

        // For local customized additions to the main menu.
        if ($this->custom_builder
                && method_exists($this->custom_builder, "userMenu"))
            $menu = $this->custom_builder->userMenu($factory, $options);
        $menu = $this->styleMenuBootstrap($menu, $options);
        return $menu;
    }
}
