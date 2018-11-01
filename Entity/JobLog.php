<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * JobLog - In and out of work.
 *
 * @ORM\Table(name="crewcall_joblog")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\JobLogRepository")
 * @Gedmo\Loggable
 */
class JobLog
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
     * @ORM\Column(name="intime", type="datetime", nullable=false)
     * @Gedmo\Versioned
     */
    private $in;

    /**
     * @var string
     *
     * @ORM\Column(name="outtime", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $out;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStatesList")
     */
    private $state;

    /**
     * @var array
     *
     * @ORM\Column(name="attributes", type="json_array")
     */
    private $attributes = array();

    /**
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="joblogs")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)
     */
    private $job;

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
     * @return JobLog
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
        return ExternalEntityConfig::getStatesFor('JobLog');
    }

    /**
     * Get states list
     *
     * @return array 
     */
    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('JobLog'));
    }

    /**
     * Set attributes
     *
     * @param array $attributes
     *
     * @return JobLog
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get in
     *
     * @return \DateTime
     */
    public function getIn()
    {
        return $this->in;
    }

    /**
     * set in
     *
     * @param \datetime $in
     *
     * @return joblog
     */
    public function setIn($in)
    {
        $this->in = $in;

        return $this;
    }

    /**
     * Get in
     *
     * @return \DateTime
     */
    public function getOut()
    {
        // Presume they ended their shift when it's ended.
        // TODO: Consider setting this so there is always something in the
        // table.
        if (!$this->out)
            return $this->getShift()->getEnd();
        return $this->out;
    }

    /**
     * Set out
     *
     * @param \datetime $out
     *
     * @return joblog
     */
    public function setOut($out)
    {
        $this->out = $out;

        return $this;
    }

    public function getPerson()
    {
        return $this->getJob()->getPerson();
    }

    public function getShift()
    {
        return $this->getJob()->getShift();
    }

    public function getJob()
    {
        return $this->job;
    }

    public function setJob(Job $job = null)
    {
        if ($this->job !== null) {
            $this->job->removeJobLog($this);
        }

        if ($job !== null) {
            $job->addJobLog($this);
        }

        $this->job = $job;
        return $this;
    }
}
