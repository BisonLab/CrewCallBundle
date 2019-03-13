<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_person_function_event")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\PersonFunctionEventRepository")
 * @Gedmo\Loggable
 */
class PersonFunctionEvent
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="person_function_events")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="person_function_events")
     * @ORM\JoinColumn(name="function_id", referencedColumnName="id", nullable=false)
     */
    private $function;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="person_function_events")
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
            $this->person->removeEventFunction($this);
        }

        if ($person !== null) {
            $person->addPersonFunctionEvent($this);
        }

        $this->person = $person;
        return $this;
    }

    public function getFunction()
    {
        return $this->function;
    }

    public function setFunction(FunctionEntity $function = null)
    {
        if ($this->function !== null) {
            $this->function->removePersonFunctionEvent($this);
        }

        if ($function !== null) {
            $function->addPersonFunctionEvent($this);
        }

        $this->function = $function;
        return $this;
    }

    /**
     * Set event
     *
     * @param \CrewCallBundle\Entity\Event $event
     *
     * @return PersonFunctionEvent
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

    public function __toString()
    {
        return $this->getFunction()->getName();
    }
}
