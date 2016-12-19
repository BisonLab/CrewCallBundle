<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This is the "An organization provides five roadies" equivalent of Job, which
 * is the connection between one individual and a function in a shift.
 * 
 * @ORM\Entity
 * @ORM\Table(name="crewcall_shift_organization")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\ShiftFunctionOrganizationRepository")
 * @Gedmo\Loggable
 */
class ShiftFunctionOrganization
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ShiftFunction", inversedBy="organizations")
     * @ORM\JoinColumn(name="shift_function_id", referencedColumnName="id", nullable=FALSE)
     */
    private $shift_function;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="shift_functions")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=FALSE)
     */
    private $organization;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     * @Gedmo\Versioned
     */
    private $amount;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return ShiftFunctionOrganization
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set shift_function
     *
     * @param \CrewCallBundle\Entity\ShiftFunction $shift_function
     *
     * @return ShiftFunctionOrganization
     */
    public function setShift(\CrewCallBundle\Entity\ShiftFunction $shift_function)
    {
        $this->shift_function = $shift_function;

        return $this;
    }

    /**
     * Get shift_function
     *
     * @return \CrewCallBundle\Entity\Shift
     */
    public function getShiftFunction()
    {
        return $this->shift_function;
    }

    /**
     * Set organization
     *
     * @param \CrewCallBundle\Entity\Organization $organization
     *
     * @return ShiftFunctionOrganization
     */
    public function setOrganization(\CrewCallBundle\Entity\Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \CrewCallBundle\Entity\Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
