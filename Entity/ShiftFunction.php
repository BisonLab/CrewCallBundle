<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_shift_function")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\ShiftFunctionRepository")
 * @Gedmo\Loggable
 */
class ShiftFunction
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Shift", inversedBy="shift_functions")
     * @ORM\JoinColumn(name="shift_id", referencedColumnName="id", nullable=false)
     */
    private $shift;

    /**
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="shift_functions")
     * @ORM\JoinColumn(name="function_id", referencedColumnName="id", nullable=false)
     */
    private $function;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     * @Gedmo\Versioned
     */
    private $amount;

    /**
     * @ORM\OneToMany(targetEntity="Job", mappedBy="shift_function", cascade={"remove"})
     */
    private $jobs;

    /**
     * @ORM\OneToMany(targetEntity="ShiftFunctionOrganization", mappedBy="shift_function", cascade={"remove"})
     */
    private $shift_function_organizations;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return ShiftFunction
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
     * @return ShiftFunction
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
     * Set function
     *
     * @param \CrewCallBundle\Entity\FunctionEntity $function
     *
     * @return ShiftFunction
     */
    public function setFunction(\CrewCallBundle\Entity\FunctionEntity $function)
    {
        $this->function = $function;

        return $this;
    }

    /**
     * Get function
     *
     * @return \CrewCallBundle\Entity\FunctionEntity
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Add job
     *
     * @param \CrewCallBundle\Entity\Job $job
     *
     * @return Shift
     */
    public function addJob(\CrewCallBundle\Entity\Job $job)
    {
        $this->jobs[] = $job;

        return $this;
    }

    /**
     * Remove job
     *
     * @param \CrewCallBundle\Entity\Job $job
     */
    public function removeJob(\CrewCallBundle\Entity\Job $job)
    {
        $this->jobs->removeElement($job);
    }

    /**
     * Get jobs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Add shift_function_organizations
     *
     * @param \CrewCallBundle\Entity\ShiftOrganization $shift_function_organizations
     *
     * @return Shift
     */
    public function addShiftFunctionOrganization(\CrewCallBundle\Entity\ShiftFunctionOrganization $shift_function_organizations)
    {
        $this->shift_function_organizations[] = $shift_function_organizations;

        return $this;
    }

    /**
     * Remove shift_function_organizations
     *
     * @param \CrewCallBundle\Entity\ShiftFunctionOrganization $shift_function_organizations
     */
    public function removeOrganization(\CrewCallBundle\Entity\ShiftFunctionOrganization $shift_function_organizations)
    {
        $this->shift_function_organizations->removeElement($shift_function_organizations);
    }

    /**
     * Get shift_function_organizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getShiftFunctionOrganizations()
    {
        return $this->shift_function_organizations;
    }

    public function getBooked()
    {
        $booked = 0;
        foreach ($this->getJobs() as $j) {
            if ($j->isBooked()) $booked++;
        }
        foreach ($this->getShiftFunctionOrganizations() as $sfo) {
            // If they are mentioned, they are booked. Aka amount is by
            // definition booked.
            $booked += $sfo->getAmount();
        }
        return $booked;
    }

    public function __toString()
    {
        return (string)$this->getFunction();
    }
}
