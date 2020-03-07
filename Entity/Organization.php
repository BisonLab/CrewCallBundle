<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use CrewCallBundle\Lib\ExternalEntityConfig;
use CrewCallBundle\Entity\EmbeddableAddress;

/**
 * @ORM\Entity
 * @ORM\Table(name="crewcall_organization")
 * @ORM\Entity(repositoryClass="CrewCallBundle\Repository\OrganizationRepository")
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, unique=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * Most countries has an organization number of some sort. This is for that.
     * @var string
     * @ORM\Column(name="organization_number", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $organization_number;

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
     * @ORM\Embedded(class="EmbeddableAddress")
     **/
    private $visit_address;

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
    private $state = "ACTIVE";

    /**
     * @ORM\OneToMany(targetEntity="PersonRoleOrganization", mappedBy="organization", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $person_role_organizations;

    /**
     * @ORM\OneToMany(targetEntity="ShiftOrganization", mappedBy="organization", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $shift_organizations;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="organization", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $events;

    /**
     * @ORM\OneToMany(targetEntity="OrganizationContext", mappedBy="owner", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $contexts;

    public function __construct()
    {
        $this->person_role_organizations = new ArrayCollection();
        $this->contexts = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->visit_address = new EmbeddableAddress();
        $this->postal_address = new EmbeddableAddress();
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
     *
     * @param string $name
     * @return Location
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set organizationNumber
     *
     * @param string $organizationNumber
     *
     * @return Organization
     */
    public function setOrganizationNumber($organizationNumber)
    {
        $this->organization_number = $organizationNumber;

        return $this;
    }

    /**
     * Get organizationNumber
     *
     * @return string
     */
    public function getOrganizationNumber()
    {
        return $this->organization_number;
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
     * @param \CrewCallBundle\Entity\EmbeddableAddress $visitAddress
     *
     * @return Organization
     */
    public function setVisitAddress(EmbeddableAddress $VisitAddress)
    {
        $this->visit_address = $visitAddress;

        return $this;
    }

    /**
     * Get visitAddress
     *
     * @return \CrewCallBundle\Entity\EmbeddableAddress
     */
    public function getVisitAddress()
    {
        return $this->visit_address;
    }

    /**
     * Set address
     *
     * @param \CrewCallBundle\Entity\EmbeddableAddress $address
     *
     * @return Organization
     */
    public function setPostalAddress(EmbeddableAddress $PostalAddress)
    {
        $this->postal_address = $PostalAddress;

        return $this;
    }

    /**
     * Get postal_address
     *
     * @return \CrewCallBundle\Entity\EmbeddableAddress
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
     * Get state label
     *
     * @return string 
     */
    public function getStateLabel($state = null)
    {
        $state = $state ?: $this->getState();
        return ExternalEntityConfig::getStatesFor('Organization')[$state]['label'];
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
    public static function getStatesList()
    {
        return array_keys(ExternalEntityConfig::getStatesFor('Organization'));
    }

    /*
     * The big "Does this work or not" is wether this getter should include
     * *all* functions. Alas also those in the person_* tables. I say "Yes", but
     * then it's not that easy to handle these functions in picker-forms.
     */
    public function getPersonRoleOrganizations()
    {
        return $this->person_role_organizations;
    }

    public function addPersonRoleOrganization(PersonRoleOrganization $pfo)
    {
        if (!$this->person_role_organizations->contains($pfo)) {
            $this->person_role_organizations->add($pfo);
        }
        return $this;
    }

    public function removePersonRoleOrganization(PersonRoleOrganization $pfo)
    {
        if ($this->person_role_organizations->contains($pfo)) {
            $this->person_role_organizations->removeElement($pfo);
        }
        return $this;
    }

    /*
     * The downside of this "helper" is that we don't see the function, aka
     * what they do in the organization.
     */
    public function getPeople()
    {
        $persons = new ArrayCollection();
        foreach ($this->getPersonRoleOrganizations() as $pfo) {
            if (!$persons->contains($pfo->getPerson()))
                $persons->add($pfo->getPerson());
        } 
        return $persons;
    }

    /**
     * Get events
     *
     * @return objects 
     */
    public function getEvents($sort_order = null)
    {
        if ($sort_order == "ASC") {
            $iterator = $this->events->getIterator();
            $iterator->uasort(function ($a, $b) {
                if ($a->getStart()->format("U") == $b->getStart()->format("U")) return 0;
                return ($a->getStart()->format("U") < $b->getStart()->format("U")) ? -1 : 1;
            });
            return new ArrayCollection(iterator_to_array($iterator));
        }
        if ($sort_order == "DESC") {
            $iterator = $this->events->getIterator();
            $iterator->uasort(function ($a, $b) {
                if ($a->getStart()->format("U") == $b->getStart()->format("U")) return 0;
                return ($a->getStart()->format("U") > $b->getStart()->format("U")) ? -1 : 1;
            });
            return new ArrayCollection(iterator_to_array($iterator));
        }
        return $this->events;
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
    public function addContext(OrganizationContext $context)
    {
        $this->contexts[] = $context;
        $context->setOwner($this) ;
    }

    /**
     * Remove contexts
     *
     * @param OrganizationContext $contexts
     */
    public function removeContext(OrganizationContext $contexts)
    {
        $this->contexts->removeElement($contexts);
    }

    /**
     * Is this deleeteable? If any event connected to it, no.
     *
     * @return boolean
     */
    public function isDeleteable()
    {
        return count($this->getEvents()) == 0;
    }

    public function __toString()
    {
        return $this->name;
    }
}
