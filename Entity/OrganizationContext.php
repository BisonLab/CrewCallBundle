<?php

namespace CrewCallBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * CrewCallBundle\Entity\OrganizationContext
 *
 * @ORM\Table(name="crewcall_organizationcontext")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\OrganizationContextRepository")
 */
class OrganizationContext
{
    use \BisonLab\CommonBundle\Entity\ContextBaseTrait;
    /**
     * @var mixed
     *
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="contexts")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
     */
    private $owner;

    public function getOwnerEntityAlias()
    {
        return "CrewCallBundle:Organization";
    }
}
