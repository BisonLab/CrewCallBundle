<?php

namespace CrewCallBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CrewCallBundle\Entity\PersonFunction;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\Shift;
use CrewCallBundle\Entity\ShiftOrganization;
use CrewCallBundle\Entity\Event;

/*
 * This thingie will make it possible to add forms with content based on entity
 * in file lists.
 */

class FormCreator
{
    /*
     * I rerally thought I could do without this now. Because AutoWire.
     * 
     */
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function createDeleteForm($frog)
    {
        /*
         * When adding to this one, remember to make the createDeleteForm pulic
         * in the controller.
         */
        if ($frog instanceof PersonFunction) {
            $pcf = new \CrewCallBundle\Controller\PersonFunctionController();
            $pcf->setContainer($this->container);
            return $pcf->createDeleteForm($frog)->createView();
        }
    }
}
