<?php

namespace CrewCallBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $local_builder = null;

    public function __construct()
    {
        if (class_exists('AppBundle\Menu\Builder')) {
            $this->local_builder = new \AppBundle\Menu\Builder();
        }
    }

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Home', array('route' => 'homepage'));

        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $menu->addChild('Admin Stuff', array('route' => ''));
            $menu['Admin Stuff']->addChild('Message Types', array('route' => 'messagetype'));
        }

        // For local additions to the main menu.
        if ($this->local_builder
                && method_exists($this->local_builder, "mainMenu"))
            return $this->local_builder->mainMenu($factory, $options, $menu);
        return $menu;
    }
}
