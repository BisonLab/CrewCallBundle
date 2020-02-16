<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * PersonState - In and out of work.
 *
 * @ORM\Table(name="crewcall_personstate")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\PersonStateRepository")
 * @Gedmo\Loggable
 */
class PersonState
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="from_date", type="date", nullable=true)
     * @Gedmo\Versioned
     */
    private $from_date;

    /**
     * @var string
     *
     * @ORM\Column(name="to_date", type="date", nullable=true)
     * @Gedmo\Versioned
     */
    private $to_date;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=false)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStatesList")
     */
    private $state;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="person_states")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     */
    private $person;

    public function __construct()
    {
        $this->from_date = new \DateTime();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return PersonState
     */
    public function setState($state)
    {
        if ($state == $this->state) return $this;
        $state = strtoupper($state);
        if (!isset(self::getStates()[$state])) {
            throw new \InvalidArgumentException(
                sprintf('The "%s" state is not a valid state.', $state));
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
        return ExternalEntityConfig::getStatesFor('Person')[$state]['label'];
    }

    /**
     * Get states
     *
     * @return array 
     */
    public static function getStates()
    {
        return ExternalEntityConfig::getStatesFor('Person');
    }

    /**
     * Get states list
     *
     * @return array 
     */
    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('Person'));
    }

    /**
     * Get from_date
     *
     * @return \DateTime
     */
    public function getFromDate()
    {
        return $this->from_date;
    }

    /**
     * set from_date
     *
     * @param \datetime $from_date
     *
     * @return joblog
     */
    public function setFromDate($from_date)
    {
        $this->from_date = $from_date;

        return $this;
    }

    /**
     * Get to_date
     *
     * @return \DateTime
     */
    public function getToDate()
    {
        return $this->to_date;
    }

    /**
     * Set to_date
     *
     * @param \date $to_date
     *
     * @return joblog
     */
    public function setToDate($to_date)
    {
        $this->to_date = $to_date;
        return $this;
    }

    public function getPerson()
    {
        return $this->person;
    }

    public function setPerson(Person $person)
    {
        $this->person = $person;
        return $this;
    }

    /*
     * Helpers
     */

    public function getDays()
    {
        $ftime = $this->getFromDate()->format("U");
        if ($this->getToDate())
            $ttime = $this->getToDate()->format("U");
        else
            $ttime = time();
        $seconds = $ttime - $ftime;
        return floor($seconds / 86400) + 1;
    }

    public function __toString()
    {
        return $this->getState();
    } 
}
