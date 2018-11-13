<?php

namespace CrewCallBundle\Lib\Dashboarder;

class PersonalMessages
{
    private $router;
    private $entitymanager;
    private $twig;

    /*
     * I may need a lot more here. Too bad I can't use the container.
     */
    public function __construct($router, $entitymanager, $twig)
    {
        $this->router = $router;
        $this->entitymanager = $entitymanager;
        $this->twig = $twig;
    }

    public function dashize(\CrewCallBundle\Entity\Person $user)
    {
        return $this->twig->render('dashboarder/personalmessages.html.twig');
    }
}
