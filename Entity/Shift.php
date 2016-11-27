<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_shift")
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
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="shifts")
     * @ORM\JoinColumn(name="functionentity_id", referencedColumnName="id", nullable=false)
     */
    private $function;

    /**
     * How many people do we need? (And can I find a better name for this
     * field?) 
     * @var string
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     * @Gedmo\Versioned
     */
    private $amount;

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
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="admin_shifts")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=FALSE)
     */
    private $admin;

    /**
     * @ORM\OneToMany(targetEntity="Interest", mappedBy="shift", cascade={"remove"})
     */
    private $interests;

    /**
     * @ORM\OneToMany(targetEntity="ShiftOrganization", mappedBy="shift", cascade={"remove"})
     */
    private $organizations;

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
        if (!in_array($state, self::getStates())) {
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
     * Constructor
     */
    public function __construct()
    {
        $this->interests = new \Doctrine\Common\Collections\ArrayCollection();
        $this->organizations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return Shift
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
     * Set admin
     *
     * @param \CrewCallBundle\Entity\Person $admin
     *
     * @return Shift
     */
    public function setAdmin(\CrewCallBundle\Entity\Person $admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return \CrewCallBundle\Entity\Person
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Add interest
     *
     * @param \CrewCallBundle\Entity\Interest $interest
     *
     * @return Shift
     */
    public function addInterest(\CrewCallBundle\Entity\Interest $interest)
    {
        $this->interests[] = $interest;

        return $this;
    }

    /**
     * Remove interest
     *
     * @param \CrewCallBundle\Entity\Interest $interest
     */
    public function removeInterest(\CrewCallBundle\Entity\Interest $interest)
    {
        $this->interests->removeElement($interest);
    }

    /**
     * Get interests
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInterests()
    {
        return $this->interests;
    }

    /**
     * Add organization
     *
     * @param \CrewCallBundle\Entity\ShiftOrganization $organization
     *
     * @return Shift
     */
    public function addOrganization(\CrewCallBundle\Entity\ShiftOrganization $organization)
    {
        $this->organizations[] = $organization;

        return $this;
    }

    /**
     * Remove organization
     *
     * @param \CrewCallBundle\Entity\ShiftOrganization $organization
     */
    public function removeOrganization(\CrewCallBundle\Entity\ShiftOrganization $organization)
    {
        $this->organizations->removeElement($organization);
    }

    /**
     * Get organizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }
}
