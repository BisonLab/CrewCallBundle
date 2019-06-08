<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\Criteria;

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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="starttime", type="datetime", nullable=false)
     * @Gedmo\Versioned
     */
    private $start;

    /**
     * @var string
     *
     * @ORM\Column(name="endtime", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $end;

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
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=true)
     */
    private $organization;

    /**
     * @ORM\OneToMany(targetEntity="PersonFunctionEvent", mappedBy="event", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $person_function_events;

    /**
     * @ORM\OneToMany(targetEntity="Shift", mappedBy="event", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     * @ORM\OrderBy({"start" = "ASC"})
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
     * @ORM\OneToMany(targetEntity="Event", mappedBy="parent", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     * @ORM\OrderBy({"start" = "ASC"})
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
        $this->children = new ArrayCollection();
        $this->shifts   = new ArrayCollection();
    }

    public function __toString()
    {
        if ($this->getParent())
            return $this->getParent()->getMainEvent() . " - " . $this->name;
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
     * Get state label
     *
     * @return string 
     */
    public function getStateLabel($state = null)
    {
        $state = $state ?: $this->getState();
        return ExternalEntityConfig::getStatesFor('Event')[$state]['label'];
    }

    /**
     * Get states and a list of them.
     *
     * @return array 
     */
    public static function getStates()
    {
        return ExternalEntityConfig::getStatesFor('Event');
    }
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
        if ($this->shifts->contains($shift))
            return $this;
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
     * Get shifts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAllShifts()
    {
        $shifts = new ArrayCollection($this->shifts->toArray());
        foreach ($this->getChildren() as $child) {
            $shifts = new ArrayCollection(array_merge($shifts->toArray() ,
                $child->getAllShifts()->toArray()));
        }
        $criteria = Criteria::create()
            ->orderBy(array("start" => Criteria::ASC));
        return $shifts->matching($criteria);
    }

    /**
     * Get jobs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAllJobs($filter = [])
    {
        $jobs = new ArrayCollection();
        foreach ($this->getAllShifts() as $shift) {
            $jobs = new ArrayCollection(array_merge($jobs->toArray() ,
                $shift->getJobs()->toArray()));
        }
        $criteria = Criteria::create()
            ->orderBy(array("start" => Criteria::ASC));
        return $jobs->matching($criteria);
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
     * Get all Children of this event
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAllChildren()
    {
        $children = $this->children->toArray();
        foreach ($this->children as $child) {
            $children = array_merge($children, $child->getAllChildren());
        }
        return $children;
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
     * Set start
     *
     * @param \DateTime $start
     *
     * @return Event
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     *
     * @return Event
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
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

    /*
     * The big "Does this work or not" is wether this getter should include
     * *all* functions. Alas also those in the person_* tables. I say "Yes", but
     * then it's not that easy to handle these functions in picker-forms.
     */
    public function getPersonFunctionEvents()
    {
        return $this->person_function_events;
    }

    public function addPersonFunctionEvent(PersonFunctionEvent $pfo)
    {
        if (!$this->person_function_events->contains($pfo)) {
            $this->person_function_events->add($pfo);
        }
        return $this;
    }

    public function removePersonFunctionEvent(PersonFunctionEvent $pfo)
    {
        if ($this->person_function_events->contains($pfo)) {
            $this->person_function_events->removeElement($pfo);
        }
        return $this;
    }

    /*
     * The downside of this "helper" is that we don't see the function, aka
     * what they do in the event.
     */
    public function getPersons($function_name = null)
    {
        $persons = new ArrayCollection();
        foreach ($this->getPersonFunctionEvents() as $pfe) {
            if ($persons->contains($pfe->getPerson()))
                continue;
            if ($function_name 
                && $function_name != $pfe->getFunction()->getName())
                    continue;
            $persons->add($pfe->getPerson());
        }
        return $persons;
    }

    public function getTotalAmountNeeded()
    {
        $amount = 0;
        foreach ($this->getShifts() as $shift) {
            $amount += $shift->getAmount();
        }
        foreach ($this->getChildren() as $child) {
            foreach ($this->getShifts() as $shift) {
                $amount += $shift->getAmount();
            }
        }
        return $amount;
    }

    /**
     * Count amount of each by state
     *
     * @return int
     */
    public function getJobsAmountByState($state = null)
    {
        $amounts = [
            'INTERESTED' => 0,
            'ASSIGNED' => 0,
            'CONFIRMED' => 0,
            ];
        foreach ($this->getShifts() as $shift) {
            foreach ($shift->getJobsAmountByState() as $s => $a) {
                if (!isset($amounts[$s]))
                    $amounts[$s] = 0;
                $amounts[$s] += $a;
            }
        }
        // Gotta ask the children aswell
        foreach ($this->getChildren() as $child) {
            foreach ($child->getJobsAmountByState() as $cs => $ca) {
                if (!isset($amounts[$cs]))
                    $amounts[$cs] = 0;
                $amounts[$cs] += $ca;
            }
        }
        if ($state)
            return $amounts[$state] ?: 0;
        return $amounts;
    }

    public function isFuture()
    {
        // As long as it hasn't finished, it's in the fuiture.
        if ($this->getEnd())
            return $this->getEnd()->getTimestamp() > time();
        // Well, no end, then it's in the future.
        return true;
    }
    public function isBooked()
    {
        return in_array($this->getState(), ExternalEntityConfig::getBookedStatesFor('Event'));
    }
    public function isOpen()
    {
        return in_array($this->getState(), ExternalEntityConfig::getOpenStatesFor('Event'));
    }
    public function isActive()
    {
        if (!$this->isFuture())
            return false;
        return in_array($this->getState(), ExternalEntityConfig::getActiveStatesFor('Event'));
    }
    public function isDone()
    {
        return in_array($this->getState(), ExternalEntityConfig::getDoneStatesFor('Event'));
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        if ($this->end && ($this->start >= $this->end)) {
            $context->buildViolation('You can not set start time to after end time.')
                ->atPath('start')
                ->addViolation();
        }
    }
}
