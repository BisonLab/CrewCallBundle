<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Event
 *
 * @ORM\Entity()
 * @ORM\Table(name="crewcall_event")
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
     * @Assert\Choice(callback = "getStates")
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
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="admin_events")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=FALSE)
     */
    private $admin;

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
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
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
        return ExternalEntityConfig::getStatesFor('Event');
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
     * Set admin
     *
     * @param \CrewCallBundle\Entity\Person $admin
     *
     * @return Event
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
}
