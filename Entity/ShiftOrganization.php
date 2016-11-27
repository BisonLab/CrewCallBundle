<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_shift_organization")
 * @Gedmo\Loggable
 */
class ShiftOrganization
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Shift", inversedBy="organizations")
     * @ORM\JoinColumn(name="shift_id", referencedColumnName="id", nullable=FALSE)
     */
    private $shift;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="shifts")
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
     * @return ShiftOrganization
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
     * Set shift
     *
     * @param \CrewCallBundle\Entity\Shift $shift
     *
     * @return ShiftOrganization
     */
    public function setShift(\CrewCallBundle\Entity\Shift $shift)
    {
        $this->shift = $shift;

        return $this;
    }

    /**
     * Get shift
     *
     * @return \CrewCallBundle\Entity\Shift
     */
    public function getShift()
    {
        return $this->shift;
    }

    /**
     * Set organization
     *
     * @param \CrewCallBundle\Entity\Organization $organization
     *
     * @return ShiftOrganization
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
