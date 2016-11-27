<?php

namespace CrewCallBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use CrewCallBundle\Lib\ExternalEntityConfig;

class CrewCallBundle extends Bundle
{
    public function __toString() { return 'CrewCallBundle'; }

    public function boot()
    {
        ExternalEntityConfig::setStatesConfig($this->container->getParameter('app.states')[(string)$this]);
    }
}
