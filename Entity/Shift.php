<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_shift")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\ShiftRepository")
 * @Gedmo\Loggable
 */
class Shift
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="shifts")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=false)
     */
    private $event;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="starttime", type="datetime", nullable=false)
     * @Gedmo\Versioned
     */
    private $start;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="endtime", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $end;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=false)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStatesList")
     */
    private $state = "REGISTERED";

    /**
     * @ORM\ManyToOne(targetEntity="FunctionEntity", inversedBy="shifts")
     * @ORM\JoinColumn(name="function_id", referencedColumnName="id", nullable=false)
     */
    private $function;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     * @Gedmo\Versioned
     */
    private $amount;

    /**
     * @ORM\OneToMany(targetEntity="Job", mappedBy="shift", cascade={"persist","remove"})
     */
    private $jobs;

    /**
     * @ORM\OneToMany(targetEntity="ShiftOrganization", mappedBy="shift", cascade={"remove"})
     */
    private $shift_organizations;

    public function __construct($options = array())
    {
        $this->jobs  = new ArrayCollection();
        $this->shift_organizations  = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set start
     *
     * @param \DateTime $start
     *
     * @return Shift
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
     * @return Shift
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
     * Set state
     *
     * @param string $state
     * @return Event
     */
    public function setState($state)
    {
        if ($state == $this->state) return $this;
        if (is_int($state)) { $state = self::getStates()[$state]; }
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
        return ExternalEntityConfig::getStatesFor('Shift');
    }

    /**
     * Get states list
     *
     * @return array 
     */
    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('Shift'));
    }

    /**
     * Set event
     *
     * @param \CrewCallBundle\Entity\Event $event
     *
     * @return Shift
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

    /**
     * Set function
     *
     * @param \CrewCallBundle\Entity\FunctionEntity $function
     *
     * @return Shift
     */
    public function setFunction(\CrewCallBundle\Entity\FunctionEntity $function)
    {
        $this->function = $function;

        return $this;
    }

    /**
     * Get function
     *
     * @return \CrewCallBundle\Entity\FunctionEntity
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return Shift
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Add job
     *
     * @param \CrewCallBundle\Entity\Job $job
     *
     * @return Shift
     */
    public function addJob(\CrewCallBundle\Entity\Job $job)
    {
        if ($this->jobs->contains($job))
            return $this;
        $this->jobs[] = $job;
        $job->setShift($this);

        return $this;
    }

    /**
     * Remove job
     *
     * @param \CrewCallBundle\Entity\Job $job
     */
    public function removeJob(\CrewCallBundle\Entity\Job $job)
    {
        $this->jobs->removeElement($job);
    }

    /**
     * Get jobs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Add shift_organizations
     *
     * @param \CrewCallBundle\Entity\ShiftOrganization $shift_organizations
     *
     * @return Shift
     */
    public function addShiftOrganization(\CrewCallBundle\Entity\ShiftOrganization $shift_organizations)
    {
        $this->shift_organizations[] = $shift_organizations;

        return $this;
    }

    /**
     * Remove shift_organizations
     *
     * @param \CrewCallBundle\Entity\ShiftOrganization $shift_organizations
     */
    public function removeOrganization(\CrewCallBundle\Entity\ShiftOrganization $shift_organizations)
    {
        $this->shift_organizations->removeElement($shift_organizations);
    }

    /**
     * Get shift_organizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getShiftOrganizations()
    {
        return $this->shift_organizations;
    }

    public function __toString()
    {
        // This is just too little, but gotta look at it later and I guess
        // adding date/time is the correct thing to do. And maybe get rid of
        // the location.
        return $this->getFunction() . " at " . $this->getEvent();
    }

    /**
     * Count jobs and personorgs by state
     *
     * @return int
     */
    public function getJobsAmountByState($state = null)
    {
        $amounts = array();
        foreach ($this->getJobs() as $job) {
            $s = $job->getState();
            if (!isset($amounts[$s]))
                $amounts[$s] = 0;
            $amounts[$s]++;
        }
        foreach ($this->getShiftOrganizations() as $so) {
            // If they are mentioned, they are booked. Aka amount is by
            // definition booked.
            $s = $so->getState();
            if (!isset($amounts[$s]))
                $amounts[$s] = 0;
            $amounts[$s] += $so->getAmount();
        }
        if ($state)
            return $amounts[$state] ?: 0;
        return $amounts;
    }

    /**
     * Get the amount of persons Booked, including organization
     *
     * @return int
     */
    public function getBookedAmount()
    {
        $booked = 0;
        foreach ($this->getJobs() as $j) {
            if ($j->isBooked()) $booked++;
        }
        foreach ($this->getShiftOrganizations() as $so) {
            // If they are mentioned, they are booked. Aka amount is by
            // definition booked.
            $booked += $so->getAmount();
        }
        return $booked;
    }

    /**
     * Get the amount of persons registered, including organization
     *
     * @return int
     */
    public function getRegisteredAmount()
    {
        $booked = $this->getJobs()->count();
        foreach ($this->getShiftOrganizations() as $so) {
            // If they are mentioned, they are booked. Aka amount is by
            // definition booked.
            $booked += $so->getAmount();
        }
        return $booked;
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
