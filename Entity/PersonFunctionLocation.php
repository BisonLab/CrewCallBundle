<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_person_function_location")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\PersonFunctionLocationRepository")
 * @Gedmo\Loggable
 */
class PersonFunctionLocation
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="person_function_locations")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="person_function_locations")
     * @ORM\JoinColumn(name="function_id", referencedColumnName="id", nullable=false)
     */
    private $function;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="person_function_locations")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=false)
     */
    private $location;

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
            $this->person->removePersonFunctionLocation($this);
        }

        if ($person !== null) {
            $person->addPersonFunctionLocation($this);
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
            $this->function->removePersonFunctionLocation($this);
        }

        if ($function !== null) {
            $function->addPersonFunctionLocation($this);
        }

        $this->function = $function;
        return $this;
    }

    /**
     * Set fromDate
     *
     * @param \DateTime $fromDate
     *
     * @return PersonFunctionLocation
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
     * @return PersonFunctionLocation
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
     * Set location
     *
     * @param \CrewCallBundle\Entity\Location $location
     *
     * @return PersonFunctionLocation
     */
    public function setLocation(\CrewCallBundle\Entity\Location $location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \CrewCallBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    public function __toString()
    {
        return $this->getFunction()->getName() . " at " . $this->getLocation()->getName();
    }
}
