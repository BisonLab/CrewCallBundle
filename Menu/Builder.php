<?php

namespace CrewCallBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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

        $menu->addChild('Home', array('route' => 'homepage'));
        $menu->addChild('My Jobs', array('route' => 'user_me'));

        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $menu->addChild('Events', array('route' => 'event_index'));
            $menu->addChild('Listings');
            $menu['Listings']->addChild('People', array('route' => 'person_index'));
            $menu['Listings']->addChild('Organizations', array('route' => 'organization_index'));
            $menu['Listings']->addChild('Location', array('route' => 'location_index'));
            $menu['Listings']->addChild('Functions', array('route' => 'function_index'));

            $menu->addChild('Admin Stuff', array('route' => ''));
            $menu['Admin Stuff']->addChild('Message Types', array('route' => 'messagetype'));
            $menu['Admin Stuff']->addChild('User Admin', array('route' => 'user'));
            $menu['Admin Stuff']->addChild('Report generator', array('route' => 'reports'));
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
        $menu = $this->sakonnin_builder->messageMenu($factory, $options);

        if ($options['container']->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $menu['Messages']->addChild('Write PM and send SMS', array('uri' => '#'));
            $menu['Messages']['Write PM and send SMS']->setLinkAttribute('onclick', 'createPmMessage("PMSMS")');
            $menu['Messages']->addChild('Write Frontpage message', array('uri' => '#'));
            $menu['Messages']['Write Frontpage message']->setLinkAttribute('onclick', 'createMessage()');

        }

        // For local customized additions to the main menu.
        if ($this->custom_builder
                && method_exists($this->custom_builder, "userMenu"))
            $menu = $this->custom_builder->userMenu($factory, $options);
        return $menu;
    }
}
