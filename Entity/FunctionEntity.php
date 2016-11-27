<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * FunctionEntity
 *
 * Yes, this should be named "Function" since that's what it is supposed to be.
 * But feel free to try.
 *
 * @ORM\Entity()
 * @ORM\Table(name="crewcall_function")
 * @UniqueEntity("name")
 * @Gedmo\Loggable
 */
class FunctionEntity
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

    /* 
     * This is for grouping the functions. Hopefully just one group and one
     * subgroup.
     *
     * I could instead do a group table with childs and parents and so on and
     * then connect these functions against one or the other.  And I may well
     * do that if this ends up being too odd for the users or code.
     */
    /**
     * @ORM\OneToMany(targetEntity="FunctionEntity", mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="children")
     * @ORM\JoinColumn(name="parent_function_id", referencedColumnName="id")
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
}
