<?php

namespace CrewCallBundle\Service;

use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Location;
use CrewCallBundle\Entity\Organization;
use CrewCallBundle\Entity\EmbeddableAddress;

/*
 * Just a service handling address formatting. Couldn't find a twig
 * output thingie so this can be that.
 *
 * As much code as possible nicked from the documentation.
 */
class Addressing
{
    private $locale;
    private $addressing;
    private $format;

    public function __construct($locale, $addressing)
    {
        $this->locale = $locale;
        $this->addressing = $addressing;
        $this->default_country_code = $addressing['default_country_code'];
        $this->default_country_name = $addressing['default_country_name'];
        $this->format = $addressing['format'];
    }

    public function compose($frog, $format = null)
    {
        // First, find a proper address.
        if ($frog instanceOf EmbeddableAddress)
            $address = $frog;
        elseif (method_exists($frog, 'getAddress'))
            $address = $frog->getAddress();
        else
            // Return null or throw exception?
            return null;

        // Names:
        $name = '';
        if (method_exists($frog, 'getName')) {
            $name = $frog->getName();
        } elseif (method_exists($frog, 'getFirstName')
                && method_exists($frog, 'getLastName')) {
            $name = $frog->getFirstName() . $frog->getLastName();
        }

        // What do we need?
        $address_lines = [];
        $address_flat = [];
        foreach ($this->format as $line) { 
            $a = [];
            foreach ($line as $elem) { 
                // This is a hack for now, TODO: expand country properly, in
                // the right moments.
                if ($elem == "country")
                    $elem = "countryCode";
                $a[$elem] = $address->get($elem);
                $address_flat[$elem] = $address->get($elem);
            }
            $address_lines[] = $a;
        }

        if ($format == "postal") {
            $output = $name . "\n";
            // Pretty sure I could to this with address map.
            foreach ($address_lines as $line) {
                $oline = '';
                foreach ($line as $elem) { 
                    if (!empty($elem))
                    $oline .= $elem;
                }
                if (!empty($oline))
                    $output .= $oline . "\n";
            }
            return $output;
        } elseif ($format == "html") {
            $hlines = [];
            // Pretty sure I could to this with address map.
            foreach ($address_lines as $line) {
                $hline = '';
                foreach ($line as $k => $elem) { 
                    if (!empty($elem))
                        $hline .= '<span class="' . $k . '">' . $elem . '</span>';
                }
                if (!empty($hline))
                    $hlines[] = $hline;
            }
            return implode("<br>", $hlines);

            $prefix = '<' . $options['html_tag'] . ' ' . $attributes . '>' . "\n";
            $suffix = "\n" . '</' . $options['html_tag'] . '>';
            $output = $prefix . $output . $suffix;
            return $html;
        } elseif ($format == "flat") {
            return $address_flat;
        } elseif ($format == "line") {
            return implode(" ", $address_flat);
        } else {
            return $address_lines;
        }
    }

    /*
     */
    public function addToForm(&$form, $frog)
    {
        $address = $this->compose($frog, "flat");

        foreach ($address as $key => $val) {
            $form->add($key);
        }
        return $form;
    }

    /*
     * Prefix is in case of more than one address per entity.
     * like "postal" for the extra postal address
     */
    public function getFormElementList($frog)
    {
        $address = $this->compose($frog, 'flat');
        $elements = [];
        // Pretty sure I could to this with address map.
        foreach ($address as $key => $val) {
            $elements[] = $key;
        }
        return $elements;
    }
}
