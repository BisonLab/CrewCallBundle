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

    /* 
     * This is for grouping the functions. Hopefully just one group and one
     * subgroup.
     *
     * I could instead do a group table with childs and parents and so on and
     * then connect these functions against one or the other.  And I may well
     * do that if this ends up being too odd for the users or code.
     */
    /**
     * @ORM\OneToMany(targetEntity="FunctionEntity", mappedBy="parent", fetch="EXTRA_LAZY", cascade={"remove"})
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
    public function getChildren()
    {
        return $this->children;
    }

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
    public function getParent()
    {
        return $this->parent;
    }

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
     * Get personFunctions
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
     * Get People
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPeople()
    {
        $people = array();
        foreach ($this->person_functions as $pf) {
            $people[] = $pf->getPerson();
        }
        return $people;
    }

    /**
     * Get All People, aka, from the function children aswell.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAllPeople()
    {
        $people = $this->getPeople();
        foreach ($this->getChildren() as $child) {
            $people = array_merge($people, $child->getAllPeople());
        }
        return $people;
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
     * Add Shift
     *
     * @param \CrewCallBundle\Entity\Shift $shift
     *
     * @return Shift
     */
    public function addShift(\CrewCallBundle\Entity\Shift $shift)
    {
        $this->shifts[] = $shift;

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
}
