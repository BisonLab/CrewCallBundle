<?php

namespace CrewCallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Address
 *
 * I'm trying to find some generic address handling, like conoutry dependans
 * forms and address labels. The CommerceGuys \ Addressing seems to do this job.
 * At least some of it. The other candidate was sylius/addressing-bundle but
 * it's not ready for Symfony 3 yet.
 *
 * Both are somewhat confusing, since they seem to mean that a stored address
 * shall contain person names or organization. I am going to use this as an
 * address storage pointed to by Person, Location and Organization, alas the
 * names are stored elsewhere.
 *
 * So, we'll find out how successful this is. I'll stick to the Address model
 * from CommerceGuys anyway. It is for Drupal and we'll find out how useful it
 * is quite soon. Have time to replace it all later anyway.
 *
 * @ORM\Entity()
 * @ORM\Table(name="crewcall_address")
 * @Gedmo\Loggable
 */
class Address
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * The two-letter country code.
     *
     * @var string
     * @ORM\Column(name="country_code", type="string", length=4, nullable=false)
     * @Gedmo\Versioned
     */
    private $countryCode;

    /**
     * The top-level administrative subdivision of the country.
     *
     * @var string
     * @ORM\Column(name="administrative_area", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $administrativeArea;

    /**
     * The locality (i.e. city).
     *
     * @var string
     * @ORM\Column(name="locality", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $locality;

    /**
     * The dependent locality (i.e. neighbourhood).
     *
     * @var string
     * @ORM\Column(name="dependent_locality", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $dependentLocality;

    /**
     * The postal code.
     *
     * @var string
     * @ORM\Column(name="postal_code", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $postalCode;

    /**
     * The sorting code.
     *
     * @var string
     * @ORM\Column(name="sorting_code", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $sortingCode;

    /**
     * The first line of the address block.
     *
     * @var string
     * @ORM\Column(name="address_line_1", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $addressLine1;

    /**
     * The second line of the address block.
     *
     * @var string
     * @ORM\Column(name="address_line_2", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $addressLine2;

    /**
     * The locale.
     *
     * @var string
     * @ORM\Column(name="locale", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $locale;

    public function __construct($options = array())
    {
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
     * Set countryCode
     *
     * @param string $countryCode
     *
     * @return Address
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Get countryCode
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Set administrativeArea
     *
     * @param string $administrativeArea
     *
     * @return Address
     */
    public function setAdministrativeArea($administrativeArea)
    {
        $this->administrativeArea = $administrativeArea;

        return $this;
    }

    /**
     * Get administrativeArea
     *
     * @return string
     */
    public function getAdministrativeArea()
    {
        return $this->administrativeArea;
    }

    /**
     * Set locality
     *
     * @param string $locality
     *
     * @return Address
     */
    public function setLocality($locality)
    {
        $this->locality = $locality;

        return $this;
    }

    /**
     * Get locality
     *
     * @return string
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * Set dependentLocality
     *
     * @param string $dependentLocality
     *
     * @return Address
     */
    public function setDependentLocality($dependentLocality)
    {
        $this->dependentLocality = $dependentLocality;

        return $this;
    }

    /**
     * Get dependentLocality
     *
     * @return string
     */
    public function getDependentLocality()
    {
        return $this->dependentLocality;
    }

    /**
     * Set postalCode
     *
     * @param string $postalCode
     *
     * @return Address
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get postalCode
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set sortingCode
     *
     * @param string $sortingCode
     *
     * @return Address
     */
    public function setSortingCode($sortingCode)
    {
        $this->sortingCode = $sortingCode;

        return $this;
    }

    /**
     * Get sortingCode
     *
     * @return string
     */
    public function getSortingCode()
    {
        return $this->sortingCode;
    }

    /**
     * Set addressLine1
     *
     * @param string $addressLine1
     *
     * @return Address
     */
    public function setAddressLine1($addressLine1)
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    /**
     * Get addressLine1
     *
     * @return string
     */
    public function getAddressLine1()
    {
        return $this->addressLine1;
    }

    /**
     * Set addressLine2
     *
     * @param string $addressLine2
     *
     * @return Address
     */
    public function setAddressLine2($addressLine2)
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    /**
     * Get addressLine2
     *
     * @return string
     */
    public function getAddressLine2()
    {
        return $this->addressLine2;
    }

    /**
     * Set locale
     *
     * @param string $locale
     *
     * @return Address
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
