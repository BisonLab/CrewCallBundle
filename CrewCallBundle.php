<?php

namespace CrewCallBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use CrewCallBundle\Lib\ExternalEntityConfig;
use CrewCallBundle\DependencyInjection\Compiler\AddTemplatePathPass;

class CrewCallBundle extends Bundle
{
    public function __toString() { return 'CrewCallBundle'; }

    public function boot()
    {
        ExternalEntityConfig::setStatesConfig($this->container->getParameter('crewcall.states'));
        ExternalEntityConfig::setTypesConfig($this->container->getParameter('crewcall.types'));
        ExternalEntityConfig::setSystemRoles($this->container->getParameter('crewcall.system_roles'));
    }

    /*
     * Concept pulled from KnpMenuBundle 
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new AddTemplatePathPass());
    }
}
