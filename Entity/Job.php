<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * Job
 *
 * @ORM\Table(name="crewcall_job")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\JobRepository")
 * @Gedmo\Loggable
 */
class Job
{
    use \BisonLab\CommonBundle\Entity\AttributesTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStatesList")
     */
    private $state;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="jobs")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="Shift", inversedBy="jobs")
     * @ORM\JoinColumn(name="shift_id", referencedColumnName="id", nullable=false)
     */
    private $shift;

    /**
     * @ORM\OneToMany(targetEntity="JobLog", mappedBy="job", cascade={"remove", "persist"})
     */
    private $joblogs;

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
     * @return Job
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
     * Get states
     *
     * @return array 
     */
    public static function getStates()
    {
        return ExternalEntityConfig::getStatesFor('Job');
    }

    /**
     * Get states list
     *
     * @return array 
     */
    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('Job'));
    }

    public function getPerson()
    {
        return $this->person;
    }

    public function setPerson(Person $person = null)
    {
        if ($this->person !== null) {
            $this->person->removeJob($this);
        }

        if ($person !== null) {
            $person->addJob($this);
        }

        $this->person = $person;
        return $this;
    }

    public function getShift()
    {
        return $this->shift;
    }

    public function setShift(Shift $shift = null)
    {
        if ($this->shift !== null) {
            $this->shift->removeJob($this);
        }

        if ($shift !== null) {
            $shift->addJob($this);
        }

        $this->shift = $shift;
        return $this;
    }

    /**
     * Add joblog
     *
     * @param \CrewCallBundle\Entity\JobLog $joblog
     *
     * @return Shift
     */
    public function addJobLog(\CrewCallBundle\Entity\JobLog $joblog)
    {
        $this->joblogs[] = $joblog;

        return $this;
    }

    /**
     * Remove job
     *
     * @param \CrewCallBundle\Entity\JobLog $joblog
     */
    public function removeJobLog(\CrewCallBundle\Entity\JobLog $joblog)
    {
        $this->joblogs->removeElement($joblog);
    }

    /**
     * Get joblogs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getJobLogs()
    {
        return $this->joblogs;
    }

    public function getFunction()
    {
        return $this->getShift()->getFunction();
    }

    public function getEvent()
    {
        return $this->getShift()->getEvent();
    }

    public function getLocation()
    {
        return $this->getEvent()->getLocation();
    }

    public function isBooked()
    {
        return in_array($this->getState(), ExternalEntityConfig::getBookedStatesFor('Job'));
    }

    public function __toString()
    {
        return (string)$this->getFunction();
    }
}
