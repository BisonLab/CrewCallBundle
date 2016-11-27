<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_organization")
 * @UniqueEntity("name")
 * @Gedmo\Loggable
 */
class Organization
{
    use \BisonLab\CommonBundle\Entity\AttributesTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * The only phone number here.
     * TODO: Find a good solution for dynamically adding numbers, URLs, emails
     * and so on.
     *
     * (The Context system could be used for that, but not entirely sure about
     * the need.)
     * (But.. System:Facebook, ObjectName:Username, ExternalID:thomasez )
     * (Which means everything with "one line addressing" can be handled.)
     *
     * @var string
     * @ORM\Column(name="office_phone_number", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $office_phone_number;

    /**
     * But one single email address here is OK. It's not meant for a person
     * (that should be gotten through the functions between person and
     * organization, like "CONTACT_PERSON"), but "post@foocompany.bar".
     *
     * @var string
     * @ORM\Column(name="office_email", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $office_email;

    /**
     * @ORM\ManyToOne(targetEntity="Address")
     * @ORM\JoinColumn(name="visit_address_id", referencedColumnName="id")
     * @Gedmo\Versioned
     **/
    private $visit_address;

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
     * @ORM\OneToMany(targetEntity="PersonFunctionOrganization", mappedBy="organization", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     */
    private $functions;

    public function __construct()
    {
        parent::__construct();
        // your own logic
        $this->functions = new ArrayCollection();
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
     * @return Organization
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
        return ExternalEntityConfig::getStatesFor('Organization');
    }

    /*
     * The big "Does this work or not" is wether this getter should include
     * *all* functions. Alas also those in the person_* tables. I say "Yes", but
     * then it's not that easy to handle these functions in picker-forms.
     */
    public function getFunctions()
    {
        return $this->functions->toArray();
    }

    public function addFunction(FunctionEntity $function)
    {
        if (!$this->functions->contains($function)) {
            $this->functions->add($function);
        }
        return $this;
    }

    public function removeFunction(FunctionEntity $function)
    {
        if ($this->functions->contains($function)) {
            $this->functions->removeElement($function);
        }
        return $this;
    }

    /**
     * Then, the connection between Person and Organization. Inbetween you have
     * a person_organization table with the function, 
     */

    /*
     * This does not really give us the organizations, but the table inbetween.
     * 
     * But we could try to make this return Persons, with the Functions
     * attached to it somehow. Only the functions for that person of course.
     *
     * And it should have a simple filter on ACTIVE or not. (Within from/to
     * date.
     */
    public function getPersons()
    {
        $persons = array();
        foreach ($this->getFunctions() as $f) {
            $persons += $f->getPersons();
        } 
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
     * Set officePhoneNumber
     *
     * @param string $officePhoneNumber
     *
     * @return Organization
     */
    public function setOfficePhoneNumber($officePhoneNumber)
    {
        $this->office_phone_number = $officePhoneNumber;

        return $this;
    }

    /**
     * Get officePhoneNumber
     *
     * @return string
     */
    public function getOfficePhoneNumber()
    {
        return $this->office_phone_number;
    }

    /**
     * Set officeEmail
     *
     * @param string $officeEmail
     *
     * @return Organization
     */
    public function setOfficeEmail($officeEmail)
    {
        $this->office_email = $officeEmail;

        return $this;
    }

    /**
     * Get officeEmail
     *
     * @return string
     */
    public function getOfficeEmail()
    {
        return $this->office_email;
    }

    /**
     * Set visitAddress
     *
     * @param \CrewCallBundle\Entity\Address $visitAddress
     *
     * @return Organization
     */
    public function setVisitAddress(\CrewCallBundle\Entity\Address $visitAddress = null)
    {
        $this->visit_address = $visitAddress;

        return $this;
    }

    /**
     * Get visitAddress
     *
     * @return \CrewCallBundle\Entity\Address
     */
    public function getVisitAddress()
    {
        return $this->visit_address;
    }
}
