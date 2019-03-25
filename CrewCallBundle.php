<?php

namespace CrewCallBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use CrewCallBundle\Lib\ExternalEntityConfig;
use CrewCallBundle\DependencyInjection\Compiler\AddTemplatePathPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CrewCallBundle extends Bundle
{
    public function __toString() { return 'CrewCallBundle'; }

    public function boot()
    {
        ExternalEntityConfig::setStatesConfig($this->container->getParameter('app.states')[(string)$this]);
        ExternalEntityConfig::setTypesConfig($this->container->getParameter('app.types')[(string)$this]);
        ExternalEntityConfig::setSystemRoles($this->container->getParameter('crewcall.system_roles'));
    }

    /* Concept pulled from KnpMenuBundle */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new AddTemplatePathPass());
    }
}
