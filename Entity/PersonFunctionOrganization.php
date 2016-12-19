<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_person_function_organization")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\PersonFunctionOrganizationRepository")
 * @Gedmo\Loggable
 */
class PersonFunctionOrganization
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="functions")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=FALSE)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="persons")
     * @ORM\JoinColumn(name="function_id", referencedColumnName="id", nullable=FALSE)
     */
    private $function;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="person_functions")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=FALSE)
     */
    private $organization;

    /**
     * @var string
     *
     * @ORM\Column(name="from_date", type="date", nullable=false)
     * @Gedmo\Versioned
     */
    private $from_date;

    /**
     * @var string
     *
     * @ORM\Column(name="to_date", type="date", nullable=true)
     * @Gedmo\Versioned
     */
    private $to_date;

    public function __construct()
    {
        $this->from_date = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPerson()
    {
        return $this->person;
    }

    public function setPerson(Person $person = null)
    {
        if ($this->person !== null) {
            $this->person->removeOrganizationFunction($this);
        }

        if ($person !== null) {
            $person->addPersonFunctionOrganization($this);
        }

        $this->person = $person;
        return $this;
    }

    public function getFunction()
    {
        return $this->function;
    }

    public function setFunction(FunctionEntity $function = null)
    {
        if ($this->function !== null) {
            $this->function->removePersonFunctionOrganization($this);
        }

        if ($function !== null) {
            $function->addPersonFunctionOrganization($this);
        }

        $this->function = $function;
        return $this;
    }

    /**
     * Set fromDate
     *
     * @param \DateTime $fromDate
     *
     * @return PersonFunctionOrganization
     */
    public function setFromDate($fromDate)
    {
        $this->from_date = $fromDate;

        return $this;
    }

    /**
     * Get fromDate
     *
     * @return \DateTime
     */
    public function getFromDate()
    {
        return $this->from_date;
    }

    /**
     * Set toDate
     *
     * @param \DateTime $toDate
     *
     * @return PersonFunctionOrganization
     */
    public function setToDate($toDate)
    {
        $this->to_date = $toDate;

        return $this;
    }

    /**
     * Get toDate
     *
     * @return \DateTime
     */
    public function getToDate()
    {
        return $this->to_date;
    }

    /**
     * Set organization
     *
     * @param \CrewCallBundle\Entity\Organization $organization
     *
     * @return PersonFunctionOrganization
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

    public function __toString()
    {
        return $this->getFunction()->getName();
    }
}
