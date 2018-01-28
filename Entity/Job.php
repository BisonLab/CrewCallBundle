<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * Job
 *
 * @ORM\Table(name="crewcall_job")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\JobRepository")
 * @Gedmo\Loggable
 */
class Job
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStatesList")
     */
    private $state;

    /**
     * @var array
     *
     * @ORM\Column(name="attributes", type="json_array")
     */
    private $attributes = array();

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="jobs")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="Shift", inversedBy="jobs")
     * @ORM\JoinColumn(name="shift_id", referencedColumnName="id", nullable=false)
     */
    private $shift;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Event
     */
    public function setState($state)
    {
        if ($state == $this->state) return $this;
        $state = strtoupper($state);
        if (!isset(self::getStates()[$state])) {
            throw new \InvalidArgumentException(sprintf('The "%s" state is not a valid state.', $state));
        }

        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get states
     *
     * @return array 
     */
    public static function getStates()
    {
        return ExternalEntityConfig::getStatesFor('Job');
    }

    /**
     * Get states list
     *
     * @return array 
     */
    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('Job'));
    }

    /**
     * Set attributes
     *
     * @param array $attributes
     *
     * @return Job
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getPerson()
    {
        return $this->person;
    }

    public function setPerson(Person $person = null)
    {
        if ($this->person !== null) {
            $this->person->removeJob($this);
        }

        if ($person !== null) {
            $person->addJob($this);
        }

        $this->person = $person;
        return $this;
    }

    public function getShift()
    {
        return $this->shift;
    }

    public function setShift(Shift $shift = null)
    {
        if ($this->shift !== null) {
            $this->shift->removeJob($this);
        }

        if ($shift !== null) {
            $shift->addJob($this);
        }

        $this->shift = $shift;
        return $this;
    }

    public function getFunction()
    {
        return $this->getShift()->getFunction();
    }

    public function isBooked()
    {
        return in_array($this->getState(), ExternalEntityConfig::getBookedStatesFor('Job'));
    }
}
