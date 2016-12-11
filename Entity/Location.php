<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Location
 *
 * @ORM\Entity()
 * @ORM\Table(name="crewcall_location")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\LocationRepository")
 * @UniqueEntity("name")
 * @Gedmo\Loggable
 */
class Location
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
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Address")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    /* 
     * Locations. Not to be confused with addresses since a location can be
     * "The bar in the left corner of stage 2" or "The tent at field 4".
     *
     * I could instead do a group table with childs and parents and so on and
     * then connect these locations against one or the other.  And I may well
     * do that if this ends up being too odd for the users or code.
     */
    /**
     * @ORM\OneToMany(targetEntity="Location", mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="children")
     * @ORM\JoinColumn(name="parent_location_id", referencedColumnName="id")
     * @Gedmo\Versioned
     */
    private $parent;

    public function __construct($options = array())
    {
        $this->children = new ArrayCollection();
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
     * @return Location
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
     * @return Location
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
     * @param \CrewCallBundle\Entity\Location $child
     * @return Location
     */
    public function addChild(\CrewCallBundle\Entity\Location $child)
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Remove children
     *
     * @param \CrewCallBundle\Entity\Location $child
     */
    public function removeChild(\CrewCallBundle\Entity\Location $child)
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
     * @param \CrewCallBundle\Entity\Location $parent
     * @return Location
     */
    public function setParent(\CrewCallBundle\Entity\Location $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \CrewCallBundle\Entity\Location 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set address
     *
     * @param \CrewCallBundle\Entity\Address $address
     *
     * @return Location
     */
    public function setAddress(\CrewCallBundle\Entity\Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \CrewCallBundle\Entity\Address
     */
    public function getAddress()
    {
        return $this->address;
    }
}
