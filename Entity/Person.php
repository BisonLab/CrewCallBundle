<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Entity\PersonContext;
use CrewCallBundle\Entity\PersonState;
use CrewCallBundle\Entity\EmbeddableAddress;
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
     * Override FOSUserBundle User base class default role.
     */
    const ROLE_DEFAULT = 'ROLE_PERSON';

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
     * @Assert\NotBlank
     */
    private $first_name;

    /**
     * @var string
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    private $last_name;

    /**
     * @var string
     * @ORM\Column(name="full_name", type="string", length=255, nullable=true)
     *
     * This one is not to be set by anything else than this Entity.
     */
    private $full_name;

    /**
     * Looks odd, but age may be quite useful in many cases.
     * @var string
     * @ORM\Column(name="date_of_birth", type="date", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $date_of_birth;

    /**
     * Another odd one, but it's an increasingly hot topic.
     * @var string
     * @ORM\Column(name="diets", type="array", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $diets;

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
     * @ORM\Embedded(class="EmbeddableAddress")
     **/
    private $address;

    /**
     * @ORM\Embedded(class="EmbeddableAddress")
     **/
    private $postal_address;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=40, nullable=true)
     * @Gedmo\Versioned
     * @Assert\Choice(callback = "getStatesList")
     */
    private $state;

    /**
     * This is for the non-connected functions.
     * @ORM\OneToMany(targetEntity="PersonFunction", mappedBy="person",
     * cascade={"remove"})
     */
    private $person_functions;

    /**
     * This is really functions, but since we have three (four) ways for a
     * function to be connected to this Person object we have to define each
     * by the other end of the person_function_ connection.
     * @ORM\OneToMany(targetEntity="PersonFunctionOrganization", mappedBy="person", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $person_function_organizations;

    /**
     * This is for the actual jobs.
     * @ORM\OneToMany(targetEntity="Job", mappedBy="person", fetch="EXTRA_LAZY", cascade={"remove"})
     * @ORM\OrderBy({"start" = "ASC"})
     */
    private $jobs;

    /**
     * This is for states. A person shall only be able to have one at all
     * time, but we need the history and need to set states in the future
     * (Vacation)
     * @ORM\OneToMany(targetEntity="PersonState", mappedBy="person", fetch="LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"from_date" = "ASC"})
     */
    private $person_states;

    /**
     * @ORM\OneToMany(targetEntity="PersonContext", mappedBy="owner", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $contexts;

    public function __construct()
    {
        parent::__construct();
        // your own logic
        $this->person_function_organizations = new ArrayCollection();
        $this->contexts  = new ArrayCollection();
        $this->person_states  = new ArrayCollection();
        $this->address = new EmbeddableAddress();
        $this->postal_address = new EmbeddableAddress();
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
        $this->setFullName();

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
        $this->setFullName();

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

    // Concatenate the two above. Looks odd, but we do store the full name in
    // the database so we gotta do it like this.
    private function setFullName()
    {
        $this->full_name =  $this->getFirstName() . " " . $this->getLastName();
    }

    public function getFullName()
    {
        return $this->full_name ?: $this->getUserName();
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
     * Set diets
     *
     * @param string $diets
     *
     * @return Person
     */
    public function setDiets($diets)
    {
        $this->diets = $diets;

        return $this;
    }

    /**
     * Get diets
     *
     * @return string
     */
    public function getDiets()
    {
        return $this->diets ?: array();
    }

    /**
     * Get diets
     *
     * @return string
     */
    public function getDietsLabels()
    {
        $labels = array();
        $dtypes = ExternalEntityConfig::getTypesFor('Person', 'Diet');
        foreach ($this->getDiets() as $d) {
            $labels[] = $dtypes[$d]['label'];
        }
        return $labels;
    }

    /*
     * I'll use "DietTypes" here since I store the options in types.yml.
     */
    public static function getDietTypes()
    {
        return array_keys(ExternalEntityConfig::getTypesFor('Person', 'Diet'));
    }

    public static function getDietTypesAsChoiceArray()
    {
        return ExternalEntityConfig::getTypesAsChoicesFor('Person', 'Diet');
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
     * Set Address
     *
     * @param string $Address
     *
     * @return Person
     */
    public function setAddress(EmbeddableAddress $Address)
    {
        $this->address = $Address;

        return $this;
    }

    /**
     * Get Address
     *
     * @return \CrewCallBundle\Entity\EmbeddableAddress
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set postalAddress
     *
     * @param string $postalAddress
     *
     * @return Person
     */
    public function setPostalAddress(EmbeddableAddress $postalAddress)
    {
        $this->postal_address = $postalAddress;

        return $this;
    }

    /**
     * Get postalAddress
     *
     * @return string
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
    public function setState($state, $options = array())
    {
        if (empty($options) && $state == $this->getState()) return $this;

        $curstate = $this->getCurrentState();
        $newstate = new PersonState();
        $newstate->setState($state);
        if (empty($options)) {
            $curstate->setToDate(new \DateTime('yesterday'));
        } elseif (isset($options['from_date'])) {

        }
        $this->addState($newstate);
        return $this;

        // TODO: Put this one into the daily state changer.
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

    /*
     * Add a PersonState.
     * Not sure how much validation and functionality I should put here, but 
     * I guess it's the rightest place since this is wqhere everything must
     * go.
     */
    public function addState(PersonState $state)
    {
        if (!$this->person_states->contains($state)) {
            if (!$state->getPerson())
                $state->setPerson($this);
            $this->person_states->add($state);
        }
        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->getCurrentState()
            ? $this->getCurrentState()->getState() : "";
    }

    /**
     * Get current state
     *
     * @return string
     */
    public function getCurrentState()
    {
        // TODO: Gotta filter old states better when there are many.
        $now = new \DateTime();
        foreach ($this->getStates() as $ps) {
            // There is always a from date.
            if ($ps->getFromDate() > $now)
                continue;
            // But not a to_date.
            if ($ps->getToDate() !== null && $ps->getToDate() < $now)
                continue;
            // Are we left with the only viable state now?
            return $ps;
        }
        return null;
    }

    /**
     * This is (annoyingly) different from the other getStates, but I canæt
     * come up with a better name and "getPersonStates" is even more annoying.
     *
     * @return hash
     */
    public function getStates()
    {
        return $this->person_states ?: new ArrayCollection();
    }

    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('Person'));
    }

    /**
     * Get enabled, the override.
     *
     * @return bool 
     */
    public function getEnabled()
    {
            return true;
        if (in_array($this->getState(), ExternalEntityConfig::getEnableLoginStatesFor('Person'))) {
            return true;
        } else {
            return false;
        }
    }
    public function isEnabled()
    {
        /*
         * Fallback, if no state. Which should only occure if you create the
         * users the wrong way. Or in the CLI, when starting the project.
         */
        if (!$this->getState()) return $this->enabled;
        return $this->getEnabled();
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
     * Get Organizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizations($active = true)
    {
        $o = new ArrayCollection();
        foreach ($this->getPersonFunctionOrganizations() as $pfo) {
            $o[] = $pfo->getOrganization();
        }
        return $o;
    }

    /**
     * Add job
     *
     * @param \CrewCallBundle\Entity\Job $job
     *
     * @return Person
     */
    public function addJob(\CrewCallBundle\Entity\Job $job)
    {
        if (!$this->jobs->contains($job)) {
            $this->jobs->add($job);
        }

        return $this;
    }

    /**
     * Remove job
     *
     * @param \CrewCallBundle\Entity\Job $job
     */
    public function removeJob(\CrewCallBundle\Entity\Job $job)
    {
        if ($this->jobs->contains($job)) {
            $this->jobs->removeElement($job);
        }
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

    public function __toString()
    {
        return $this->getFullName();
    }
}
