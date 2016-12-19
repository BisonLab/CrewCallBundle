<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * Event
 *
 * @ORM\Entity()
 * @ORM\Table(name="crewcall_event")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\EventRepository")
 * @UniqueEntity("name")
 * @Gedmo\Loggable
 */
class Event
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, unique=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=false)
     * @Gedmo\Versioned
     */
    private $description;

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
     * I've written that I had to have states here, but can't remember why
     * right now.
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStatesList")
     */
    private $state;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="events")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     */
    private $location;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="events")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     */
    private $organization;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="manager_events")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=FALSE)
     */
    private $manager;

    /**
     * @ORM\OneToMany(targetEntity="Shift", mappedBy="shifts", cascade={"remove"})
     */
    private $shifts;

    /* 
     * If an event is a festival or tournament, it can be useful as a parent
     * for alot of sub events. Aka we want parent/child functionality.
     *
     * I could instead do a group table with childs and parents and so on and
     * then connect these events against one or the other.  And I may well
     * do that if this ends up being too odd for the users or code.
     */
    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="children")
     * @ORM\JoinColumn(name="parent_event_id", referencedColumnName="id")
     * @Gedmo\Versioned
     */
    private $parent;

    public function __construct($options = array())
    {
        $this->from_date = new \DateTime();
        $this->children = new ArrayCollection();
        $this->shifts   = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getMainEvent()
    {
        if ($this->getParent())
            return $this->getParent()->getMainEvent();
        else
            return $this->getName();
    }
    
    /*
     * Automatically generated getters and setters below this
     */

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @param string $name
     * @return Event
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Event
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
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
dump(self::getStates());
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
        return ExternalEntityConfig::getStatesFor('Event');
    }

    /**
     * Get states list
     *
     * @return array 
     */
    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('Event'));
    }
    /**
     * Add shift
     *
     * @param \CrewCallBundle\Entity\Shift $shift
     * @return Shift
     */
    public function addShift(\CrewCallBundle\Entity\Shift $shift)
    {
        $this->shifts[] = $shift;
        $shift->setEvent($this);

        return $this;
    }

    /**
     * Remove shifts
     *
     * @param \CrewCallBundle\Entity\Shift $shift
     */
    public function removeShift(\CrewCallBundle\Entity\Shift $shift)
    {
        $this->shifts->removeElement($shift);
    }

    /**
     * Get shifts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getShifts()
    {
        return $this->shifts;
    }

    /**
     * Add child
     *
     * @param \CrewCallBundle\Entity\Event $child
     * @return Event
     */
    public function addChild(\CrewCallBundle\Entity\Event $child)
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Remove children
     *
     * @param \CrewCallBundle\Entity\Event $child
     */
    public function removeChild(\CrewCallBundle\Entity\Event $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \CrewCallBundle\Entity\Event $parent
     * @return Event
     */
    public function setParent(\CrewCallBundle\Entity\Event $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \CrewCallBundle\Entity\Event 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set fromTime
     *
     * @param \DateTime $fromTime
     *
     * @return Event
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
     * @return Event
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
     * Set location
     *
     * @param \CrewCallBundle\Entity\Location $location
     *
     * @return Event
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
     * Set organization
     *
     * @param \CrewCallBundle\Entity\Organization $organization
     *
     * @return Event
     */
    public function setOrganization(\CrewCallBundle\Entity\Organization $organization = null)
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

    /**
     * Set manager
     *
     * @param \CrewCallBundle\Entity\Person $manager
     *
     * @return Event
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
}
