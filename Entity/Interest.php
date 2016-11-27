<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Interest
 *
 * @ORM\Table(name="interest")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\InterestRepository")
 */
class Interest
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
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStates")
     */
    private $state;

    /**
     * @var array
     *
     * @ORM\Column(name="attributes", type="json_array")
     */
    private $attributes;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="interests")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=FALSE)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="Shift", inversedBy="interests")
     * @ORM\JoinColumn(name="shift_id", referencedColumnName="id", nullable=FALSE)
     */
    private $shift;

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
     * @return Event
     */
    public function setState($state)
    {
        if ($state == $this->state) return $this;
        if (is_int($state)) { $state = self::getStates()[$state]; }
        $state = strtoupper($state);
        if (!in_array($state, self::getStates())) {
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
        return ExternalEntityConfig::getStatesFor('Event');
    }

    /**
     * Set attributes
     *
     * @param array $attributes
     *
     * @return Interest
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
}

