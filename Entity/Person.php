<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Entity\PersonContext;
use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_person")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\PersonRepository")
 * @UniqueEntity("username")
 * @Gedmo\Loggable
 */
class Person extends BaseUser
{
    use \BisonLab\CommonBundle\Entity\AttributesTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $first_name;

    /**
     * @var string
     * @ORM\Column(name="lastt_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $last_name;

    /**
     * Looks odd, but age may be quite useful in many cases.
     * @var string
     * @ORM\Column(name="date_of_birth", type="date", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $date_of_birth;

    /**
     * @var string
     * @ORM\Column(name="mobile_phone_number", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $mobile_phone_number;

    /**
     * The last of two phone numbers.
     * Two should be enough for a table, the rest should be added as 
     * attributes, same with Facebook/Google usernames/addresses 
     *
     * @var string
     * @ORM\Column(name="home_phone_number", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $home_phone_number;

    /**
     * @ORM\ManyToOne(targetEntity="Address")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     * @Gedmo\Versioned
     **/
    private $address;

    /**
     * @ORM\ManyToOne(targetEntity="Address")
     * @ORM\JoinColumn(name="postal_address_id", referencedColumnName="id")
     * @Gedmo\Versioned
     **/
    private $postal_address;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStates")
     */
    private $state;

    /**
     * This is for the non-connected functions.
     * @ORM\OneToMany(targetEntity="PersonFunction", mappedBy="person",
     * cascade={"remove"})
     */
    private $person_functions;

    /**
     * This is really functions, but since we have three (four) ways for a function to
     * be connected to this Person object we have to define each by the other
     * end of the person_function_ connection.
     * @ORM\OneToMany(targetEntity="PersonFunctionOrganization", mappedBy="person", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     */
    private $person_function_organizations;

    /**
     * @ORM\OneToMany(targetEntity="PersonContext", mappedBy="owner", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $contexts;

    public function __construct()
    {
        parent::__construct();
        // your own logic
        $this->organizations = new ArrayCollection();
        $this->contexts  = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set first_name
     *
     * @param string $first_name
     *
     * @return Person
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * Get first_name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last_name
     *
     * @param string $last_name
     *
     * @return Person
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Get last_name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Set date_of_birth
     *
     * @param string $date_of_birth
     *
     * @return Person
     */
    public function setDateOfBirth($date_of_birth)
    {
        $this->date_of_birth = $date_of_birth;

        return $this;
    }

    /**
     * Get date_of_birth
     *
     * @return string
     */
    public function getDateOfBirth()
    {
        return $this->date_of_birth;
    }

    /**
     * Set mobilePhoneNumber
     *
     * @param string $mobilePhoneNumber
     *
     * @return Person
     */
    public function setMobilePhoneNumber($mobilePhoneNumber)
    {
        $this->mobile_phone_number = $mobilePhoneNumber;

        return $this;
    }

    /**
     * Get mobilePhoneNumber
     *
     * @return string
     */
    public function getMobilePhoneNumber()
    {
        return $this->mobile_phone_number;
    }

    /**
     * Set homePhoneNumber
     *
     * @param string $homePhoneNumber
     *
     * @return Person
     */
    public function setHomePhoneNumber($homePhoneNumber)
    {
        $this->home_phone_number = $homePhoneNumber;

        return $this;
    }

    /**
     * Get homePhoneNumber
     *
     * @return string
     */
    public function getHomePhoneNumber()
    {
        return $this->home_phone_number;
    }

    /**
     * Set address
     *
     * @param \CrewCallBundle\Entity\Address $address
     *
     * @return Person
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

    /**
     * Set postalAddress
     *
     * @param \CrewCallBundle\Entity\Address $postalAddress
     *
     * @return Person
     */
    public function setPostalAddress(\CrewCallBundle\Entity\Address $postalAddress = null)
    {
        $this->postal_address = $postalAddress;

        return $this;
    }

    /**
     * Get postalAddress
     *
     * @return \CrewCallBundle\Entity\Address
     */
    public function getPostalAddress()
    {
        return $this->postal_address;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Person
     */
    public function setState($state)
    {
        if ($state == $this->state) return $this;
        $state = strtoupper($state);
        if (!isset(self::getStates()[$state])) {
            throw new \InvalidArgumentException(sprintf('The "%s" state is not a valid state.', $state));
        }
        // Handle login enabling.
        // (Enabled in the fos user bundle takes care of that part.)
        if (in_array($state, ExternalEntityConfig::getEnableLoginStatesFor('Person'))) {
            $this->setEnabled(true);
        } else {
            $this->setEnabled(false);
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
        return ExternalEntityConfig::getStatesFor('Person');
    }

    /*
     * The big "Does this work or not" is wether this getter should include
     * *all* functions. Alas also those in the person_* tables. I say "Yes",
     * but then it's not that easy to handle these functions in
     * picker-forms.
     */

    /**
     * Add personFunction
     *
     * @param \CrewCallBundle\Entity\PersonFunction $personFunction
     *
     * @return Person
     */
    public function addPersonFunction(\CrewCallBundle\Entity\PersonFunction $personFunction)
    {
        $this->person_functions[] = $personFunction;

        return $this;
    }

    /**
     * Remove personFunction
     *
     * @param \CrewCallBundle\Entity\PersonFunction $personFunction
     */
    public function removePersonFunction(\CrewCallBundle\Entity\PersonFunction $personFunction)
    {
        $this->person_functions->removeElement($personFunction);
    }

    /**
     * Get personFunctions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonFunctions()
    {
        return $this->person_functions;
    }

    /**
     * Add personFunctionOrganization
     *
     * @param \CrewCallBundle\Entity\PersonFunctionOrganization $personFunctionOrganization
     *
     * @return Person
     */
    public function addPersonFunctionOrganization(\CrewCallBundle\Entity\PersonFunctionOrganization $personFunctionOrganization)
    {
        $this->person_function_organizations[] = $personFunctionOrganization;

        return $this;
    }

    /**
     * Remove personFunctionOrganization
     *
     * @param \CrewCallBundle\Entity\PersonFunctionOrganization $personFunctionOrganization
     */
    public function removePersonFunctionOrganization(\CrewCallBundle\Entity\PersonFunctionOrganization $personFunctionOrganization)
    {
        $this->person_function_organizations->removeElement($personFunctionOrganization);
    }

    /**
     * Get personFunctionOrganizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonFunctionOrganizations()
    {
        return $this->person_function_organizations;
    }

    /**
     * Get contexts
     *
     * @return objects 
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * add context
     *
     * @return mixed 
     */
    public function addContext(PersonContext $context)
    {
        $this->contexts[] = $context;
        $context->setOwner($this) ;
    }

    /**
     * Remove contexts
     *
     * @param PersonContext $contexts
     */
    public function removeContext(PersonContext $contexts)
    {
        $this->contexts->removeElement($contexts);
    }

    public function getFullName()
    {
        return $this->getFirstName() . " " . $this->getLastName();
    }

    public function __toString()
    {
        return $this->getFullName();
    }
}
