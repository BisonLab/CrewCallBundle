<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * FunctionEntity
 *
 * Yes, this should be named "Function" since that's what it is supposed to be.
 * But feel free to try.
 *
 * @ORM\Entity()
 * @ORM\Table(name="crewcall_function")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\FunctionEntityRepository")
 * @UniqueEntity("name")
 * @Gedmo\Loggable
 */
class FunctionEntity
{
    // Not sure if I need it, but they might be useful.
    // (And i need it for migration for now.)
    use \BisonLab\CommonBundle\Entity\AttributesTrait;

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
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStatesList")
     */
    private $state;

    /**
     * @var string $function_type
     *
     * @ORM\Column(name="function_type", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getFunctionTypes")
     */
    private $function_type;

    /* 
     * This is for grouping the functions. Hopefully just one group and one
     * subgroup.
     *
     * I could instead do a group table with childs and parents and so on and
     * then connect these functions against one or the other.  And I may well
     * do that if this ends up being too odd for the users or code.
     */
    /**
     * @ORM\OneToMany(targetEntity="FunctionEntity", mappedBy="parent", cascade={"remove"})
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="children")
     * @ORM\JoinColumn(name="parent_function_id", referencedColumnName="id")
     * @Gedmo\Versioned
     */
    private $parent;

    /**
     * This is for the non-connected functions.
     * @ORM\OneToMany(targetEntity="PersonFunction", mappedBy="function",
     * cascade={"remove"})
     */
    private $person_functions;

    /**
     * @ORM\OneToMany(targetEntity="PersonFunctionOrganization", mappedBy="function", cascade={"remove"})
     */
    private $person_function_organizations;

    /**
     * @ORM\OneToMany(targetEntity="PersonFunctionEvent", mappedBy="function", cascade={"remove"})
     */
    private $person_function_events;

    /**
     * This is for the non-connected functions.
     * @ORM\OneToMany(targetEntity="Shift", mappedBy="function",
     * cascade={"remove"})
     */
    private $shifts;

    public function __construct($options = array())
    {
        $this->children = new ArrayCollection();
        $this->person_functions = new ArrayCollection();
        $this->person_function_organizations = new ArrayCollection();
        $this->person_function_events = new ArrayCollection();
        $this->shifts = new ArrayCollection();
    }

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
     * @return FunctionEntity
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
     * @return FunctionEntity
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
     * @return Person
     */
    public function setState($state)
    {
        if ($state == $this->state) return $this;
        $state = strtoupper($state);
        if (!in_array($state, self::getStatesList())) {
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
     * Get states and a list of them.
     *
     * @return array 
     */
    public static function getStates()
    {
        return ExternalEntityConfig::getStatesFor('Function');
    }
    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('Function'));
    }

    /**
     * Set function_type
     *
     * @param string $function_type
     *
     * @return Person
     */
    public function setFunctionType($function_type)
    {
        $this->function_type = $function_type;

        return $this;
    }

    /**
     * Get function_type
     *
     * @return string
     */
    public function getFunctionType()
    {
        return $this->function_type;
    }

    /**
     * Get function_type
     *
     * @return string
     */
    public function getFunctionTypeLabel()
    {
        $ftypes = ExternalEntityConfig::getTypesFor('FunctionEntity', 'FunctionType');
        return $ftypes[$this->function_type]['label'];
    }

    /*
     *
     */
    public static function getFunctionTypes()
    {
        return array_keys(ExternalEntityConfig::getTypesFor('FunctionEntity', 'FunctionType'));
    }

    public static function getFunctionTypesAsChoiceArray()
    {
        return ExternalEntityConfig::getTypesAsChoicesFor('FunctionEntity', 'FunctionType');
    }

    /**
     * Add child
     *
     * @param \CrewCallBundle\Entity\FunctionEntity $child
     * @return FunctionEntity
     */
    public function addChild(\CrewCallBundle\Entity\FunctionEntity $child)
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Remove children
     *
     * @param \CrewCallBundle\Entity\FunctionEntity $child
     */
    public function removeChild(\CrewCallBundle\Entity\FunctionEntity $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
/*
    public function getChildren()
    {
        return $this->children;
    }
 */

    /**
     * Set parent
     *
     * @param \CrewCallBundle\Entity\FunctionEntity $parent
     * @return FunctionEntity
     */
    public function setParent(\CrewCallBundle\Entity\FunctionEntity $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \CrewCallBundle\Entity\FunctionEntity 
     */
/*
    public function getParent()
    {
        return $this->parent;
    }
 */

    /**
     * Add personFunction
     *
     * @param \CrewCallBundle\Entity\PersonFunction $personFunction
     *
     * @return Person
     */
    public function addPersonFunction(\CrewCallBundle\Entity\PersonFunction $personFunction)
    {
        $this->person_functions[] = $personFunction;

        return $this;
    }

    /**
     * Remove personFunction
     *
     * @param \CrewCallBundle\Entity\PersonFunction $personFunction
     */
    public function removePersonFunction(\CrewCallBundle\Entity\PersonFunction $personFunction)
    {
        $this->person_functions->removeElement($personFunction);
    }

    /**
     * Get personFunctions (AKA Skills)
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonFunctions()
    {
        return $this->person_functions;
    }

    /**
     * Add personFunctionOrganization
     *
     * @param \CrewCallBundle\Entity\PersonFunctionOrganization $personFunctionOrganization
     *
     * @return Person
     */
    public function addPersonFunctionOrganization(\CrewCallBundle\Entity\PersonFunctionOrganization $personFunctionOrganization)
    {
        $this->person_function_organizations[] = $personFunctionOrganization;

        return $this;
    }

    /**
     * Remove personFunctionOrganization
     *
     * @param \CrewCallBundle\Entity\PersonFunctionOrganization $personFunctionOrganization
     */
    public function removePersonFunctionOrganization(\CrewCallBundle\Entity\PersonFunctionOrganization $personFunctionOrganization)
    {
        $this->person_function_organizations->removeElement($personFunctionOrganization);
    }

    /**
     * Get personFunctionOrganizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonFunctionOrganizations()
    {
        return $this->person_function_organizations;
    }

    /**
     * Add personFunctionEvent
     *
     * @param \CrewCallBundle\Entity\PersonFunctionEvent $personFunctionEvent
     *
     * @return Person
     */
    public function addPersonFunctionEvent(\CrewCallBundle\Entity\PersonFunctionEvent $personFunctionEvent)
    {
        $this->person_function_events[] = $personFunctionEvent;

        return $this;
    }

    /**
     * Remove personFunctionEvent
     *
     * @param \CrewCallBundle\Entity\PersonFunctionEvent $personFunctionEvent
     */
    public function removePersonFunctionEvent(\CrewCallBundle\Entity\PersonFunctionEvent $personFunctionEvent)
    {
        $this->person_function_events->removeElement($personFunctionEvent);
    }

    /**
     * Get personFunctionEvents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonFunctionEvents()
    {
        return $this->person_function_events;
    }

    /**
     * Add Shift
     *
     * @param \CrewCallBundle\Entity\Shift $shift
     *
     * @return Shift
     */
    public function addShift(\CrewCallBundle\Entity\Shift $shift)
    {
        if ($this->shifts->contains($shift))
            return $this;
        $this->shifts[] = $shift;
        $shift->setFunction($this);

        return $this;
    }

    /**
     * Remove Shift
     *
     * @param \CrewCallBundle\Entity\Shift $shift
     */
    public function removeShift(\CrewCallBundle\Entity\Shift $shift)
    {
        $this->shifts->removeElement($shift);
    }

    /**
     * Get Shifts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getShifts()
    {
        return $this->shifts;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /*
     * Helper functions.
     */

    /**
     * Get People
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPeople($active_only = true)
    {
        $people = new ArrayCollection();
        foreach ($this->person_functions as $pf) {
            if ($active_only && !in_array($pf->getPerson()->getState(),
                    ExternalEntityConfig::getActiveStatesFor('Person')))
                continue;
            if (!$people->contains($pf->getPerson()))
                $people->add($pf->getPerson());
        }
        foreach ($this->person_function_organizations as $pfo) {
            if ($active_only && !in_array($pfo->getPerson()->getState(),
                    ExternalEntityConfig::getActiveStatesFor('Person')))
                continue;
            if (!$people->contains($pfo->getPerson()))
                $people->add($pfo->getPerson());
        }
        return $people;
    }

    /*
     * Many ways of counting, this is kinda resourceeating, but useful and
     * hopefully not too bad. If we do get performance issues we'd find out
     * hopefully and stop using it where it hurts.
     *
     * * No options: Just count personfunctions.
     * * 'function_type': By function type - TODO
     * * 'by_state': Count getPeople() and sort by state.
     * * 'function_type_by_state':  - TODO
     */
    public function countPeople($options = [])
    {
        // The simplest one.
        if (empty($options))
            return $this->personfunctions->count()
                + $this->personfunctionorganizations->count();
        if (isset($options['by_state'])) {
            $states = [];
            foreach ($this->getPeople(false) as $p) {
                if (!isset($states[$p->getState()]))
                    $states[$p->getState()] = 1;
                else
                    $states[$p->getState()]++;
            }
            return $states;
        }
    }
}
