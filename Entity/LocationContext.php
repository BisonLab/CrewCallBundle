<?php

namespace CrewCallBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * CrewCallBundle\Entity\LocationContext
 *
 * @ORM\Table(name="crewcall_locationcontext")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\LocationContextRepository")
 */
class LocationContext
{
    use \BisonLab\CommonBundle\Entity\ContextBaseTrait;
    /**
     * @var mixed
     *
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="contexts")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
     */
    private $owner;

    public function getOwnerEntityAlias()
    {
        return "CrewCallBundle:Location";
    }
}
