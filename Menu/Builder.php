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

        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $menu->addChild('Admin Stuff', array('route' => ''));
            $menu['Admin Stuff']->addChild('Message Types', array('route' => 'messagetype'));
            $menu['Admin Stuff']->addChild('User Admin', array('route' => 'user'));
        }

        $options['menu']      = $menu;
        $options['container'] = $this->container;
        // For local additions to the main menu.
        if ($this->custom_builder
                && method_exists($this->custom_builder, "mainMenu"))
            $menu = $this->custom_builder->mainMenu($factory, $options);
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
            $menu['Messages']['Write PM and send SMS']->setLinkAttribute('onclick', 'createPmSmsMessage()');
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
