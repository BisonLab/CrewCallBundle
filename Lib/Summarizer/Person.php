<?php

namespace CrewCallBundle\Lib\Summarizer;

class Person
{
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function summarize(\CrewCallBundle\Entity\Person $person, $access = null)
    {
        $summary = array();

        $summary[] = array(
            'name' => 'name',
            'value' => (string)$person,
            'label' => 'Name'
            );

        $summary[] = array(
            'name' => 'diets',
            'value' => implode(", ", $person->getDietsLabels()),
            'label' => 'Diets'
            );

        return $summary;
    }
}
