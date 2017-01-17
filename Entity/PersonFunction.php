<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_person_function", uniqueConstraints={@ORM\UniqueConstraint(name="person_function_idx", columns={"person_id", "function_id"})})
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\PersonFunctionOrganizationRepository")
 * @Gedmo\Loggable
 */
class PersonFunction
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="person_functions", cascade={"persist"})
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="person_functions", cascade={"persist"})
     * @ORM\JoinColumn(name="function_id", referencedColumnName="id", nullable=false)
     */
    private $function;

    /**
     * @var string
     *
     * @ORM\Column(name="from_date", type="date", nullable=false)
     * @Gedmo\Versioned
     */
    private $from_date;

    /**
     * @var string
     * This is basically an expiration. It's typically for licensed functions.
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
            $this->person->removePersonFunction($this);
        }

        if ($person !== null) {
            $person->addPersonFunction($this);
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
            $this->function->removePersonFunction($this);
        }

        if ($function !== null) {
            $function->addPersonFunction($this);
        }

        $this->function = $function;
        return $this;
    }

    /**
     * Set fromDate
     *
     * @param \DateTime $fromDate
     *
     * @return PersonFunction
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
     * @return PersonFunction
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

    public function __toString()
    {
        return $this->getFunction()->getName();
    }
}
