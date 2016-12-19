<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_shift")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\ShiftRepository")
 * @Gedmo\Loggable
 */
class Shift
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="shifts")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=false)
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="Shifts")
     * @ORM\JoinColumn(name="locvation_id", referencedColumnName="id", nullable=true)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="from_time", type="datetime", nullable=false)
     * @Gedmo\Versioned
     */
    private $from_time;

    /**
     * @var string
     *
     * @ORM\Column(name="to_time", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $to_time;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStates")
     */
    private $state;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="manager_shifts")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=FALSE)
     */
    private $manager;

    /**
     * @ORM\OneToMany(targetEntity="ShiftFunction", mappedBy="shift", cascade={"remove"})
     */
    private $shift_functions;

    public function __construct($options = array())
    {
        $this->shift_functions = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set fromTime
     *
     * @param \DateTime $fromTime
     *
     * @return Shift
     */
    public function setFromTime($fromTime)
    {
        $this->from_time = $fromTime;

        return $this;
    }

    /**
     * Get fromTime
     *
     * @return \DateTime
     */
    public function getFromTime()
    {
        return $this->from_time;
    }

    /**
     * Set toTime
     *
     * @param \DateTime $toTime
     *
     * @return Shift
     */
    public function setToTime($toTime)
    {
        $this->to_time = $toTime;

        return $this;
    }

    /**
     * Get toTime
     *
     * @return \DateTime
     */
    public function getToTime()
    {
        return $this->to_time;
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
        if (is_int($state)) { $state = self::getStates()[$state]; }
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
        return ExternalEntityConfig::getStatesFor('Shift');
    }

    /**
     * Set event
     *
     * @param \CrewCallBundle\Entity\Event $event
     *
     * @return Shift
     */
    public function setEvent(\CrewCallBundle\Entity\Event $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return \CrewCallBundle\Entity\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set location
     *
     * @param \CrewCallBundle\Entity\Location $location
     *
     * @return Shift
     */
    public function setLocation(\CrewCallBundle\Entity\Location $location = null)
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

    /**
     * Set function
     *
     * @param \CrewCallBundle\Entity\FunctionEntity $function
     *
     * @return Shift
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
     * Set manager
     *
     * @param \CrewCallBundle\Entity\Person $manager
     *
     * @return Shift
     */
    public function setManager(\CrewCallBundle\Entity\Person $manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * Get manager
     *
     * @return \CrewCallBundle\Entity\Person
     */
    public function getManager()
    {
        return $this->manager;
    }

    public function __toString()
    {
        // This is just too little, but gotta look at it later and I guess
        // adding date/time is the correct thing to do. And maybe get rid of
        // the location.
        return $this->getEvent() . " at " . $this->getLocation();
    }
}
