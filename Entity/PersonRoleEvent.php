<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use CrewCallBundle\Entity\Event;
use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Role;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_person_role_event")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\PersonRoleEventRepository")
 * @Gedmo\Loggable
 */
class PersonRoleEvent
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="person_role_events")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="person_role_events")
     * @ORM\JoinColumn(name="function_id", referencedColumnName="id", nullable=false)
     */
    private $function;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="person_role_events")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=false)
     */
    private $event;

    public function getId()
    {
        return $this->id;
    }

    public function getPerson()
    {
        return $this->person;
    }

    public function setPerson(Person $person = null)
    {
        if ($this->person !== null) {
            $this->person->removeEventRole($this);
        }

        if ($person !== null) {
            $person->addPersonRoleEvent($this);
        }

        $this->person = $person;
        return $this;
    }

    public function getRole()
    {
        return $this->function;
    }

    public function setRole(Role $function = null)
    {
        if ($this->function !== null) {
            $this->function->removePersonRoleEvent($this);
        }

        if ($function !== null) {
            $function->addPersonRoleEvent($this);
        }

        $this->function = $function;
        return $this;
    }

    /**
     * Set event
     *
     * @param \CrewCallBundle\Entity\Event $event
     *
     * @return PersonRoleEvent
     */
    public function setEvent(Event $event)
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

    public function __toString()
    {
        return (string)$this->getRole() . " at " . (string)$this->getEvent();
    }
}
