<?php
namespace CrewCallBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * This compiler pass adds the path for the CrewCallBundle.
 *
 * Nicked from KnpMenuBundle
 */
class AddTemplatePathPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $loaderDefinition = null;

        if ($container->hasDefinition('twig.loader.filesystem')) {
            $loaderDefinition = $container->getDefinition('twig.loader.filesystem');
        }

        if (null === $loaderDefinition) {
            return;
        }

        $path = __DIR__ .'/../..//Resources/views';
        $loaderDefinition->addMethodCall('addPath', array($path));
    }
}
