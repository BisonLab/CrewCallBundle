<?php

namespace CrewCallBundle\Service;

use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Organization;
use CrewCallBundle\Entity\Event;

/* 
 * This is the way to be able to program "Summaries" on entities. Both
 * here in the main bundle, but also customize whenever needed.
 */
class Dashboarder
{
    private $config;
    private $router;
    private $entitymanager;
    private $twig;

    /*
     * I may need a lot more here. Too bad I can't use the container.
     */
    public function __construct($config, $router, $entitymanager, $twig)
    {
        $this->config = $config;
        $this->router = $router;
        $this->entitymanager = $entitymanager;
        $this->twig = $twig;
    }

    public function dashboards(Person $user)
    {
        $dashes = [];
        foreach ($this->config['roles'] as $role => $elems) {
            if (in_array($role, $user->getRoles())) {
                $dashes += $elems;
                break;
            }
        }
        // And here, go through functions when you have something for them.

        $dashboards = [];
        foreach ($dashes as $function => $dash) {
            $dash['dashie'] = $function;
            $cust_class = '\CustomBundle\Lib\Dashboarder\\' . $function;
            $crew_class = '\CrewCallBundle\Lib\Dashboarder\\' . $function;
            if (class_exists($cust_class)) {
                $cc = new $cust_class($this->router,
                    $this->entitymanager,
                    $this->twig);
                $dash['content'] = $cc->dashize($user) ?: "";
            } elseif (class_exists($crew_class)) {
                $cc = new $crew_class($this->router,
                    $this->entitymanager,
                    $this->twig);
                $dash['content'] = $cc->dashize($user) ?: "";
            } else {
                continue;
            }
            $dashboards[] = $dash;
        }
        return $dashboards;
    }
}
