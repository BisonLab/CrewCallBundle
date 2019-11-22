<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * Role, basically the same as Function(entity) but forked away from it.
 * Ironically it is to make the whole system less complex looking.
 *
 * I tried with "Function type" but looking at the Person object still gave me
 * the creeps because of the five (Yes: 5!) ways a person could be tied to a
 * Function(Entity).
 *
 * And there is a difference between a Function/Skill and a Role albeit subtle.
 *
 * @ORM\Entity()
 * @ORM\Table(name="crewcall_role")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\RoleRepository")
 * @UniqueEntity("name")
 * @Gedmo\Loggable
 */
class Role
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
     * @ORM\OneToMany(targetEntity="PersonRoleOrganization", mappedBy="function", cascade={"remove"})
     */
    private $person_role_organizations;

    /**
     * @ORM\OneToMany(targetEntity="PersonRoleLocation", mappedBy="function", cascade={"remove"})
     */
    private $person_role_locations;

    /**
     * @ORM\OneToMany(targetEntity="PersonRoleEvent", mappedBy="function", cascade={"remove"})
     */
    private $person_role_events;

    public function __construct($options = array())
    {
        $this->person_roles = new ArrayCollection();
        $this->person_role_organizations = new ArrayCollection();
        $this->person_role_events = new ArrayCollection();
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
     * @return Role
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
     * @return Role
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
     * Add personRoleOrganization
     *
     * @param \CrewCallBundle\Entity\PersonRoleOrganization $personRoleOrganization
     *
     * @return Person
     */
    public function addPersonRoleOrganization(\CrewCallBundle\Entity\PersonRoleOrganization $personRoleOrganization)
    {
        $this->person_role_organizations[] = $personRoleOrganization;

        return $this;
    }

    /**
     * Remove personRoleOrganization
     *
     * @param \CrewCallBundle\Entity\PersonRoleOrganization $personRoleOrganization
     */
    public function removePersonRoleOrganization(\CrewCallBundle\Entity\PersonRoleOrganization $personRoleOrganization)
    {
        $this->person_role_organizations->removeElement($personRoleOrganization);
    }

    /**
     * Get personRoleOrganizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonRoleOrganizations()
    {
        return $this->person_role_organizations;
    }

    /**
     * Add personRoleLocation
     *
     * @param \CrewCallBundle\Entity\PersonRoleLocation $personRoleLocation
     *
     * @return Person
     */
    public function addPersonRoleLocation(\CrewCallBundle\Entity\PersonRoleLocation $personRoleLocation)
    {
        $this->person_role_locations[] = $personRoleLocation;

        return $this;
    }

    /**
     * Remove personRoleLocation
     *
     * @param \CrewCallBundle\Entity\PersonRoleLocation $personRoleLocation
     */
    public function removePersonRoleLocation(\CrewCallBundle\Entity\PersonRoleLocation $personRoleLocation)
    {
        $this->person_role_locations->removeElement($personRoleLocation);
    }

    /**
     * Get personRoleLocations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonRoleLocations()
    {
        return $this->person_role_locations;
    }

    /**
     * Add personRoleEvent
     *
     * @param \CrewCallBundle\Entity\PersonRoleEvent $personRoleEvent
     *
     * @return Person
     */
    public function addPersonRoleEvent(\CrewCallBundle\Entity\PersonRoleEvent $personRoleEvent)
    {
        $this->person_role_events[] = $personRoleEvent;

        return $this;
    }

    /**
     * Remove personRoleEvent
     *
     * @param \CrewCallBundle\Entity\PersonRoleEvent $personRoleEvent
     */
    public function removePersonRoleEvent(\CrewCallBundle\Entity\PersonRoleEvent $personRoleEvent)
    {
        $this->person_role_events->removeElement($personRoleEvent);
    }

    /**
     * Get personRoleEvents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonRoleEvents()
    {
        return $this->person_role_events;
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
        $shift->setRole($this);

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
        foreach ($this->person_role_organizations as $pfo) {
            if ($active_only && !in_array($pfo->getPerson()->getState(),
                    ExternalEntityConfig::getActiveStatesFor('Person')))
                continue;
            if (!$people->contains($pfo->getPerson()))
                $people->add($pfo->getPerson());
        }
        foreach ($this->person_role_locations as $pfl) {
            if ($active_only && !in_array($pfl->getPerson()->getState(),
                    ExternalEntityConfig::getActiveStatesFor('Person')))
                continue;
            if (!$people->contains($pfl->getPerson()))
                $people->add($pfl->getPerson());
        }
        foreach ($this->person_role_events as $pfe) {
            if ($active_only && !in_array($pfe->getPerson()->getState(),
                    ExternalEntityConfig::getActiveStatesFor('Person')))
                continue;
            if (!$people->contains($pfe->getPerson()))
                $people->add($pfe->getPerson());
        }
        return $people;
    }

    /*
     * Many ways of counting, this is kinda resourceeating, but useful and
     * hopefully not too bad. If we do get performance issues we'd find out
     * hopefully and stop using it where it hurts.
     *
     * * No options: Just count personroles.
     * * 'by_state': Count getPeople() and sort by their state.
     */
    public function countPeople($options = [])
    {
        // The simplest one.
        if (empty($options))
            return $this->getPeople()->count();
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
